<?php

abstract class CACOutput {
	const OUTPUT_NONE = 0;
	const OUTPUT_SCRIPT_FILE = 1;
	const OUTPUT_SCRIPT_PAGE = 2;
}

class CAC {
	private static $_fileQueue = null;

	private static function _getQueue() {
		if(self::$_fileQueue == null) {
			self::$_fileQueue = array();
		}

		return self::$_fileQueue;
	}

	//Line files up to be combined.
	public static function enqueueFiles($files = array()) {
		$q = self::_getQueue();

		if(is_array($files)) {
			self::$_fileQueue = array_merge($q, $files);
		} else {
			//add single file
			if($files == null || sizeof($files) == 0) 
				throw new Exception("No files given to enqueue.");

			self::$_fileQueue = array_push($q, $files);
			self::$_fileQueue = $q;
		}
	}

	/* Will only re-write the files if the any of the source files modified time is greater than
	   the outputFile's, or the outputFile doesn't exist, or if the outputFile's filename differs from 
	   the MD5 of the concatenation of all the queue filenames. 
	   If a filename is supplied, it is used as a prefix only.
	   By default, clearOtherFiles will tell the system to delete any existing files with the same prefix name.
	   Returns the new $filename, which will be: fileName + md5(input file names).
	*/
	public static function combineFileQueue($directory, $fileName = null, $clearOtherFiles = true) {
		$q = self::_getQueue();
		$regenerate = false;

		$ofmodtime = 0;
		$ifmodtime = 0;

		$baseDir = "";
		$ifNames = "";

		if(sizeof($q) == 0) {
			throw new Exception("Input queue has no files.");
		}

		//Combines all the input files names, and finds the last modification time.
		foreach($q as $if) {
			if(!self::_fileExists($baseDir, $if)) {
				throw new Exception("Could not find input file: " . $baseDir . $if);
			}

			$ifNames .= $if;

			//calculate largest modification time
			$mtime = filemtime($baseDir . $if);

			if($mtime > $ifmodtime)
				$ifmodtime = $mtime;
		}

		//calculate MD5 of all input names as our 'key'
		$md5InputNames = md5($ifNames);

		//now check output file
		$outputFileName = "";//$directory;
		if($fileName != null) 
			$outputFileName .= $fileName;

		//get extension of outputFile, if any was given:
		$extension = "";
		$extensionFound = preg_match('/\.[^\.]+$/i',$outputFileName,$ext) == 1;
		if($extensionFound) {
			$extension = $ext[0];
		} else {
			//no extension, so we need to guess it. We'll do that by taking the extension of the first input file:
			$if = $q[0];
			$extensionFound = preg_match('/\.[^\.]+$/i',$if,$ext) == 1;
			if($extensionFound)
				$extension = $ext[0];
		}

		if($extension == "") {
			throw new Exception("Could not determine an extension for the output filename. 
				Either there was no output filename with an extension given, or none of the 
				input files had an extension.");
		}

		//now put the MD5 before the extension:
		$outputFileName = str_replace($extension, "", $outputFileName);
		$outputFileName .= '_' . $md5InputNames . $extension;

		//If the output file exists already, it means no new files were added to the queue,
		//so we just have to compare the individual input file modification times (which we already have).
		if(file_exists($directory . $outputFileName)) {
			$ofmodtime = filemtime($baseDir . $directory . $outputFileName);

			//a file was changed, so regenerate the output file
			if ($ofmodtime < $ifmodtime) {
				$regenerate = true;
			}
		} else {
			$regenerate = true;
		}

		if($regenerate) {
			//the filename of the outputFile is the input fileName (as a prefix), then 
			//the MD5 of all the input files appended.
			self::_combineFiles($directory . $outputFileName, $q);
		}

		return $outputFileName;
	}

	//Checks the dir to see if the file exists
	static function _fileExists($dir, $file) {
		return file_exists($dir . $file);
	}

	static function outputScript($file, $outputType = CACOutput::OUTPUT_SCRIPT_FILE, $useHttps = false) {
		//Now output the file
		$output = "";

		$base = $_SERVER['DOCUMENT_ROOT'];
		$host = $_SERVER['HTTP_HOST'];

		if ($outputType == CACOutput::OUTPUT_SCRIPT_FILE) {
			$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    
    		if($useHttps)
    			$protocol = 'https://';

			$src .= $protocol . $host . $file;

			$output = '<script type="text/javascript" src="' . $src . '"></script>';

			echo $output;
		} else if ($outputType == CACOutput::OUTPUT_SCRIPT_PAGE) {
			//print the entire combined file inside a <script> tag
			$output = "<script>";
			$output .= file_get_contents($base . $file);
			$output .= "</script>";

			echo $output;
		} else {
			//Do nothing.
		}
	}

	//Does the work of combining the inputFiles to the outputFile.
	//Assumes all inputFiles exist.
	static function _combineFiles($outputFile, $inputFiles) {
		//get basePath of where all of our web files lie.
		$base =  "";//$_SERVER['DOCUMENT_ROOT'];

		$combinedOutput = "";

		foreach($inputFiles as $if) {
			$content = file_get_contents($base . $if);
			$combinedOutput .= $content;
		}

		file_put_contents($base . $outputFile, $combinedOutput);
		
		//echo "FILES COMBINED: " . $base . $outputFile;
	}
}

?>