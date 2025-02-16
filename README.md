# Sparkling Appcast

Sparkling Appcast is a WordPress plugin. It allows you to use your WordPress site to distribute your macOS App via
[Sparkle](https://sparkle-project.org/). Sparkling Appcast supports multiple tracks (alpha, beta, ...).

Sparkling Appcast exposes a new shortcode to display a list of app builds.

```
[sappcast_display_builds sappcast_track="{track-id-or-slug}"]
```

![image](https://github.com/user-attachments/assets/91388833-5935-4ce9-a18e-10857913f830)

Configure Sparkle to ingest the appcast.xml at https://your.site/wp-json/sparkling-appcast/v1/track/{track-id-or-slug}/appcast.xml.
You will see something like the following XML.

```xml
<rss xmlns:sparkle="http://www.andymatuschak.org/xml-namespaces/sparkle" xmlns:dc="http://purl.org/dc/elements/1.1/" version="2.0">
    <channel>
        <title>MyApp - Production</title>
        <link>/wp-json/sparkling-appcast/v1/track/production/appcast.xml</link>
        <item>
            <title>Version 1.0.1 (2)</title>
            <description><![CDATA[ This version is better! ]]></description>
            <sparkle:version>2</sparkle:version>
            <sparkle:shortVersionString>1.0.1</sparkle:shortVersionString>
            <pubDate>Wed, 08 Jan 2025 08:56:19 +0000</pubDate>
            <sparkle:minimumSystemVersion>14.0</sparkle:minimumSystemVersion>
            <enclosure url="http://localhost:8088/wp-content/uploads/2025/01/app_v2.zip" length="4713394" type="application/octet-stream"/>
        </item>
        <item>
            <title>Version 1.0.0 (1)</title>
            <description><![CDATA[ - New features - New bugs ]]></description>
            <sparkle:version>1</sparkle:version>
            <sparkle:shortVersionString>1.0.0</sparkle:shortVersionString>
            <pubDate>Wed, 08 Jan 2025 07:43:06 +0000</pubDate>
            <sparkle:minimumSystemVersion>14.0</sparkle:minimumSystemVersion>
            <enclosure url="http://localhost:8088/wp-content/uploads/2025/01/app_v1.zip" length="4713394" type="application/octet-stream"/>
        </item>
    </channel>
</rss>
```

## Configuration

To get started name the application you want to distribute on your WordPress site. You can find the settings page under the App Builds menu.

![image](https://github.com/user-attachments/assets/ff54e6b7-9bbc-47bf-b7fa-207d627ed548)

If you have multiple tracks besides Production, you can should add them on wp-admin under the App Builds menu.

![image](https://github.com/user-attachments/assets/92b3c0f5-a993-4b5e-af62-2aae132a978e)

## Usage

### Fastlane

We recommend you use the [`wp_sparkling_appcast` plugin](https://github.com/Usiel/fastlane-plugin-wp_sparkling_appcast) to upload assets and create builds.

### Other

To distribute a new build, you must upload the asset (1), create a build draft (2), after which you can finalize the
build and publish it (3). Steps 1 and 2 are typically executed by your build server, while step 3 is done by a human.

### 1. Uploading Asset

```bash
curl --location "localhost:8088/wp-json/wp/v2/media?status=publish&title=MyApp%20${VERSION}%20(${BUILD_NUMBER})" \
    --header "Content-Disposition: attachment; filename=\"myapp_v${VERSION}_${BUILD_NUMBER}.zip\"" \
    --header 'Content-Type: application/zip' \
    --user "${USER}:${APPLICATION_PASSWORD}" \
    --data-binary '@/path/to/asset.zip'
```

### 2. Create Build Draft

```bash
curl --location 'localhost:8088/wp-json/wp/v2/sappcast_app_build' \
    --header 'Content-Type: application/json' \
    --user "${USER}:${APPLICATION_PASSWORD}" \
    --data '{
        "meta": {
            "sappcast_app_build_version": string,
            "sappcast_app_build_number": int,
            "sappcast_app_build_min_system_version": string,
            "sappcast_app_build_attachment_id": int,
            "sappcast_app_build_changelog": string,
        },
        "sappcast_track": int,
        "status": "draft"
    }'
```

### 3. Publish Build

The user may now go to "App Build" on wp-admin and publish the draft after verifying the build. After publishing the
build will appear on the relevant appcast (`/wp-json/sparkling-appcast/v1/track/<track-id-or-slug>/appcast.xml`).

## Migration from MS App Center

Sparkling Appcast supports an automated migration from MS App Center to WP using `bin/app-center-migration.py`.

```bash
export APP_CENTER_TOKEN="..."
export WP_USER="..."
export WP_APP_PASSWORD="..."

python bin/app-center-migration.py \
    --owner "owner-name" \
    --app "app-name" \
    --wp-url "https://your-wp-site.com" \
    --track-id 123
```

## Features

Feel free to submit issues or PRs to implement features such as

- ECDSA signature support
- Automated migration from MS App Center to WP using Sparkling Appcast (currently supported via `bin/app-center-migration.py`)
- Support additional custom `sparkle:` elements (e.g. `phasedRolloutInterval`, `channel`, ...)
- Optimized UI for humans (wp-admin)
- Custom role support for uploaders
