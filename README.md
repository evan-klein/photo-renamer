# Photo Renamer

A PHP script that looks at all of the [supported photo/video files](#supported-photovideo-file-formats) in a directory, tries to figure out the date/time each photo/video was taken, and adds that to the beginning of its filename.

For example, a file named `IMG_2290.JPG` that was taken on 8/10/2021 9:46:59 AM would be renamed to `2021-08-10-09-46-59--IMG_2290.JPG`.

## Usage

1. Copy `photo-renamer.php` to the directory where your photos are located

1. Run it:

	```sh
	php photo-renamer.php
	```

## Supported photo/video file formats

| File type | Exif date | HEIF date | PNG date | File creation date |
| :-------- | :-------- | :-------- | :------- | :----------------- |
| JPG, JPEG | ✅ | ❌ | ❌ | Used only if Exif date missing |
| CR2 | ✅ | ❌ | ❌ | Used only if Exif date missing |
| HEIF, HEIC | ❌ | ✅ | ❌ | Used only if HEIF date missing |
| PNG | ❌ | ❌ | ✅ | Used only if PNG date missing |
| MOV, MP4 | ❌ | ❌ | ❌ | ✅ |
| TIFF | ❌ | ❌ | ❌ | ✅ |