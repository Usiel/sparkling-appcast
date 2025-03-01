import requests
import os
import json
from typing import Optional
import tempfile
import argparse
from urllib.parse import urlparse
from pprint import pprint

# TODO: Rewrite this to PHP and embed it as a migration tool in wp-admin.

class AppCenterMigration:
    def __init__(self, owner_name: str, app_name: str, wp_url: str, channel_id: int):
        # App Center vars
        self.app_center_api = "https://api.appcenter.ms/v0.1"
        self.owner_name = owner_name
        self.app_name = app_name

        self.app_center_token = os.environ.get("APP_CENTER_TOKEN")
        if not self.app_center_token:
            raise ValueError("APP_CENTER_TOKEN environment variable is required")

        # WP Sparkling Appcast vars
        self.production_channel_id = channel_id

        wp_user = os.environ.get("WP_USER")
        wp_app_password = os.environ.get("WP_APP_PASSWORD")
        if not wp_user or not wp_app_password:
            raise ValueError("WP_USER and WP_APP_PASSWORD environment variables are required")

        self.wp_auth = (wp_user, wp_app_password)
        self.wp_url = wp_url.rstrip("/")

    def get_app_center_releases(self) -> list:
        """Fetch all releases from App Center"""
        url = f"{self.app_center_api}/apps/{self.owner_name}/{self.app_name}/releases"
        headers = {
            "X-API-Token": self.app_center_token,
            "accept": "application/json"
        }

        response = requests.get(url, headers=headers)
        response.raise_for_status()

        releases = []
        for release in response.json():
            # Only include enabled releases distributed to all users
            if not release.get("enabled"):
                continue
            if not any(
                g["name"] not in {"All-users-of-AutoProxxy", "Alpha Testers Public Distribution"}
                for g in release.get("distribution_groups", [])
            ):
                continue

            releases.append(release)

        return releases

    def download_release(self, release_id: int, download_url: str) -> Optional[str]:
        """Download release binary to temp file"""
        headers = {"X-API-Token": self.app_center_token}
        response = requests.get(download_url, headers=headers, stream=True)
        response.raise_for_status()

        # Create temp file with .zip extension
        temp_file = tempfile.NamedTemporaryFile(suffix=".zip", delete=False)
        for chunk in response.iter_content(chunk_size=8192):
            temp_file.write(chunk)
        temp_file.close()

        return temp_file.name

    def upload_to_wordpress(self, file_path: str, version: str, build_number: str) -> int:
        """Upload release binary to WordPress media library"""
        with open(file_path, "rb") as f:
            files = {"file": f}
            headers = {
                "Content-Disposition": f'attachment; filename="AutoProxxy_{version}_{build_number}.zip"'
            }
            response = requests.post(
                f"{self.wp_url}/wp-json/wp/v2/media",
                auth=self.wp_auth,
                headers=headers,
                files=files
            )
            response.raise_for_status()
            return response.json()["id"]

    def create_build_draft(self, version: str, build_number: int,
                          changelog: str, attachment_id: int, min_version: int, uploaded_at: str):
        """Create build draft in Sparkling Appcast"""
        data = {
            "meta": {
                "sappcast_app_build_version": version,
                "sappcast_app_build_number": build_number,
                "sappcast_app_build_min_system_version": min_version,
                "sappcast_app_build_attachment_id": attachment_id,
                "sappcast_app_build_changelog": changelog,
            },
            "date": uploaded_at,
            "sappcast_channel": self.production_channel_id,
            "status": "draft"
        }

        response = requests.post(
            f"{self.wp_url}/wp-json/wp/v2/sappcast_app_build",
            auth=self.wp_auth,
            json=data
        )
        response.raise_for_status()
        return response.json()

    def publish_draft(self, build_id: int):
        data = {
            "status": "publish"
        }
        update_response = requests.post(
            f"{self.wp_url}/wp-json/wp/v2/sappcast_app_build/{build_id}",
            auth=self.wp_auth,
            json=data
        )
        update_response.raise_for_status()

    def migrate_releases(self):
        """Main migration function"""
        releases = self.get_app_center_releases()
        print(f"Found {len(releases)} releases to migrate")

        for release in releases:
            # Get release details from App Center API
            url = f"{self.app_center_api}/apps/{self.owner_name}/{self.app_name}/releases/{release['id']}"
            headers = {
                "X-API-Token": self.app_center_token,
                "accept": "application/json"
            }

            try:
                response = requests.get(url, headers=headers)
                response.raise_for_status()
                release_details = response.json()

                version = release_details["short_version"]
                build_number = release_details["version"]
                changelog = release_details.get("release_notes", "")
                download_url = release_details["download_url"]
                uploaded_at = release_details["uploaded_at"]
                min_version = release_details["min_os"]

                print(f"Migrating release {version} ({build_number})")

                # Download release
                temp_file = self.download_release(release["id"], download_url)
                if not temp_file:
                    print(f"Failed to download release {version}")
                    continue

                try:
                    # Upload to WordPress
                    attachment_id = self.upload_to_wordpress(temp_file, version, str(build_number))
                    # Create build
                    build = self.create_build_draft(
                        version=version,
                        build_number=build_number,
                        changelog=changelog,
                        attachment_id=attachment_id,
                        min_version=min_version,
                        uploaded_at=uploaded_at
                    )

                    self.publish_draft(build["id"])

                    print(f"Successfully migrated release {version}")

                finally:
                    # Clean up temp file
                    os.unlink(temp_file)

            except Exception as e:
                print(f"Failed to get release details for {version}: {str(e)}")
                continue

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Migrate releases from App Center to WordPress")
    parser.add_argument("--owner", required=True, help="App Center organization name")
    parser.add_argument("--app", required=True, help="App Center application name")
    parser.add_argument("--wp-url", required=True, help="WordPress site URL")
    parser.add_argument("--channel-id", type=int, default=1,
                       help="WordPress Sparkling Appcast channel ID (default: 1)")

    args = parser.parse_args()

    parsed_url = urlparse(args.wp_url)
    if not parsed_url.scheme or not parsed_url.netloc:
        parser.error("Invalid WordPress URL. Must include scheme (http:// or https://)")

    try:
        migrator = AppCenterMigration(args.owner, args.app, args.wp_url, args.channel_id)
        migrator.migrate_releases()
    except ValueError as e:
        print(f"Configuration error: {str(e)}")
        exit(1)
    except Exception as e:
        print(f"Migration failed: {str(e)}")
        exit(1)
