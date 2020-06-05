<?php

function isUnixTimestamp($input){
	if( is_int($input) ) return $input>=0;
	else if( is_string($input) ){
		return preg_match('/^[0-9]+$/', $input)===1;
	}
	else return false;
}

function extractExifDate($filepath){
	// Get the photo's EXIF tags
	$exif_data = exif_read_data($filepath);

	// If the photo does not have any EXIF tags, return -1
	if($exif_data===false) return -1;

	// The default value, which represents no date
	$date = -1;

	// An array of EXIF date tags to check
	$date_tags = [
		'DateTimeOriginal',
		'DateTimeDigitized',
		'DateTime',
		'FileDateTime'
	];

	// Check for the EXIF date tags, in the order specified above. First value wins
	foreach($date_tags as $date_tag){
		if(
			isset($exif_data[$date_tag])
		){
			$date = $exif_data[$date_tag];
			break;
		}
	}

	if($date==-1) return -1;
	else{
		if( isUnixTimestamp($date) ) return (int) $date;
		else return strtotime($date);
	}
}

function extractPNGDate($filepath){
	$data = file_get_contents($filepath);

	$date_regexp = '([0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\.[0-9]+)?([\-|\+][0-9]{2}:[0-9]{2})?)';
	$regexps = [
		"/<photoshop:DateCreated>$date_regexp<\/photoshop:DateCreated>/",
		"/photoshop:DateCreated=\"$date_regexp\"/",
		"/xmp:CreateDate=\"$date_regexp\"/"
	];

	foreach($regexps as $regexp){
		preg_match_all(
			$regexp,
			$data,
			$matches
		);
		if( isset($matches[1][0]) ) return strtotime($matches[1][0]);
	}

	return -1;
}

function extractHEICDate($filepath){
	$data = file_get_contents($filepath);

	$regexps = [
		'/([0-9]{4}:[0-9]{2}:[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\0[\-|\+][0-9]{2}:[0-9]{2})/'
	];

	foreach($regexps as $regexp){
		preg_match_all(
			$regexp,
			$data,
			$matches
		);
		if( isset($matches[1][0]) ) return strtotime($matches[1][0]);
	}

	return -1;
}

function getFileCreationDate($filepath){
	$filepath_clisafe = escapeshellarg($filepath);

	if(PHP_OS=='Darwin') return `stat -f %B $filepath_clisafe`;
	// TODO - add Windows support
	// TODO - add Linux support
	else return -1;
}

// The current working directory (i.e., where this script is located)
$cwd = getcwd() . '/';

// Get a list of all files in the current working directory
$files = scandir($cwd);

// For each file...
foreach($files as $file){
	$filepath = "$cwd$file";
	$path_info = pathinfo($filepath);
	$file_ext = $path_info['extension'];
	$file_ext_lc = strtolower($file_ext);
	$file_name = $path_info['filename'];

	// If the file has already been renamed, skip it
	if(
		preg_match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}\-[0-9]{2}\-[0-9]{2}\-[0-9]{2}\-\-/', $file_name)===1
	) continue;

	// If the file is a JPG/JPEG/CR2 file...
	if(
		in_array(
			$file_ext_lc,
			[
				'jpeg',
				'jpg',
				'cr2'
			]
		)
	){
		$date = extractExifDate($filepath);
		if($date==-1) $date = getFileCreationDate($filepath);
	}
	// Else if the file is a PNG file...
	else if(
		$file_ext_lc=='png'
	){
		$date = extractPNGDate($filepath);
		if($date==-1) $date = getFileCreationDate($filepath);
	}
	// Else if the file is a HEIC file...
	else if(
		$file_ext_lc=='heic'
	){
		$date = extractHEICDate($filepath);
		if($date==-1) $date = getFileCreationDate($filepath);
	}
	// Else if the file is a MOV/MP4/TIFF file...
	else if(
		in_array(
			$file_ext_lc,
			[
				'mov',
				'mp4',
				'tiff'
			]
		)
	){
		$date = getFileCreationDate($filepath);
	}
	else continue;

	if( !isUnixTimestamp($date) ) continue;

	// Format the date
	$date = date('Y-m-d-H-i-s', $date);

	// The new filename
	$new_filename = "$date--$file_name.$file_ext";

	// Output some debugging info
	echo <<<HEREDOC
"$file" renamed to "$new_filename"


HEREDOC;

	// Rename the file
	rename($filepath, "$cwd$new_filename");

	// Rename AAE file
	$aae_file = "$cwd$file_name.AAE";
	if( file_exists($aae_file) ) rename($aae_file, "$cwd$date--$file_name.AAE");
}

// Done
echo "Done";

?>