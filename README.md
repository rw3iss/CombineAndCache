CombineAndCache
===============

Very small PHP library which allows queuing of JS and CSS files, and can later combine them, cache the output to a single file, and serve this file for future requests. This is a basic server-side JS and CSS batcher interface, with plans for more later.


HOW TO USE
==========

require_once ($_SERVER["DOCUMENT_ROOT"] . '/includes/CombineAndCache.php');

$baseDir = $_SERVER['DOCUMENT_ROOT'];

//Enqueue some files which we will later combine.

CAC::enqueueFiles(array(
  $baseDir . '/js/jquery-1.11.1.min.js',
  $baseDir . '/js/global.js'
));

//Combine the files in the current queue together. It will store the combined file in $baseDir + /js/combined.js

$combinedJsFile = CAC::combineFileQueue($baseDir, '/js/combined.js');
  
//Now output the current file to the browser. It can be passed a 'true' second parameter which will rener all of the javascript directly on the page, avoiding a script request all together.

CAC::outputScript($combinedJsFile);


FUTURE
======

Next things to be implemented:

-Support for multiple caches. ie. queue files into cache A (ie. javascript), and also queue other files in cache B (ie. css). This will allow the library to become script/file-agnostic. Combine .png files and show your friends!

-Change the interface for outputScript() to support the above (right now it's kind of focused on Javascript).


IDEAS
=====
Mail any ideas to rw3iss@gmail.com!
