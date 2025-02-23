#!/bin/bash

# Function to copy files to a target directory
copy_files() {
    local target_dir=$1
    
    # Create directories if they don't exist
    mkdir -p "$target_dir"
    mkdir -p "$target_dir/includes"
    
    cp -R vendor-prefixed/ "$target_dir/"
    cp -R include "$target_dir/"
    cp README.md "$target_dir/"
    cp readme.txt "$target_dir/"
    cp LICENSE "$target_dir/"
    cp sparkling-appcast.php "$target_dir/"
}

update_svn() {
    # Get version from readme.txt
    VERSION=$(grep "Stable tag:" readme.txt | cut -d' ' -f3)
    
    if [ -z "$VERSION" ]; then
        echo "Error: Could not find version in readme.txt"
        exit 1
    fi
    
    echo "Preparing release for version $VERSION"
    
    # Update trunk
    echo "Copying files to trunk..."
    copy_files "svn/trunk"
    
    # Create and update tag
    echo "Creating tag $VERSION..."
    mkdir -p "svn/tags/$VERSION"
    copy_files "svn/tags/$VERSION"
    
    echo "SVN update completed successfully"
}

case "$1" in
    "update")
        update_svn
        ;;
    "push")
        echo "Not implemented yet"
        ;;
    *)
        echo "Usage: $0 {update|push}"
        echo "  update: Update SVN with current version"
        echo "  push: Push changes to SVN"
        exit 1
        ;;
esac
