=== Sparkling Appcast ===
Contributors: usielriedl
Tags: sparkle, appcast, distribution, macos
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 0.7
License: GPLv2 or later
Sparkling Appcast allows you to use your WordPress site to distribute your macOS App via Sparkle offering an appcast.xml endpoint.

== Description ==

Sparkling Appcast is a WordPress plugin. It allows you to use your WordPress site to distribute your macOS App via [Sparkle](https://sparkle-project.org/). Sparkling Appcast supports multiple channels (alpha, beta, ...).

Sparkling Appcast exposes a new shortcode to display a list of app builds.

`
[sappcast_display_builds sappcast_channel="{channel-id-or-slug}"]
`

Configure Sparkle to ingest the appcast.xml at https://your.site/wp-json/sparkling-appcast/v1/appcast.xml. You will see something like the following XML.

`
<rss xmlns:sparkle="http://www.andymatuschak.org/xml-namespaces/sparkle" xmlns:dc="http://purl.org/dc/elements/1.1/" version="2.0">
    <channel>
        <title>MyApp</title>
        <link>/wp-json/sparkling-appcast/v1/appcast.xml</link>
        <item>
            <title>Version 1.0.1 (2)</title>
            <description><![CDATA[ This version is better but not yet released! ]]></description>
            <sparkle:channel>Alpha</sparkle:channel>
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
`

## Configuration

To get started name the application you want to distribute on your WordPress site. You can find the settings page under the App Builds menu.

![image](https://github.com/user-attachments/assets/ff54e6b7-9bbc-47bf-b7fa-207d627ed548)

If you have multiple channels besides `stable`, you can should add them on wp-admin under the App Builds menu.

![image](https://github.com/user-attachments/assets/92b3c0f5-a993-4b5e-af62-2aae132a978e)

## Usage

### Fastlane

We recommend you use the [`wp_sparkling_appcast` plugin](https://github.com/Usiel/fastlane-plugin-wp_sparkling_appcast) to upload assets and create builds.

### Other

To distribute a new build, you must upload the asset (1), create a build draft (2), after which you can finalize the build and publish it (3). Steps 1 and 2 are typically executed by your build server, while step 3 is done by a human.

### 1. Uploading Asset

`
curl --location "localhost:8088/wp-json/wp/v2/media?status=publish&title=MyApp%20${VERSION}%20(${BUILD_NUMBER})" \
    --header "Content-Disposition: attachment; filename=\"myapp_v${VERSION}_${BUILD_NUMBER}.zip\"" \
    --header 'Content-Type: application/zip' \
    --user "${USER}:${APPLICATION_PASSWORD}" \
    --data-binary '@/path/to/asset.zip'
`

### 2. Create Build Draft

`
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
        "sappcast_channel": int,
        "status": "draft"
    }'
`

### 3. Publish Build

The user may now go to "App Build" on wp-admin and publish the draft after verifying the build. After publishing the build will appear on the relevant appcast (`/wp-json/sparkling-appcast/v1/appcast.xml`).
