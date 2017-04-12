<?php

$nl = "<br>";
if (isShellClient()) {
    $nl = "\n";
}

function writeln($s) {
    global $nl;
    echo "$s$nl";
}

function isShellClient() {
    $agent = trim(@$_SERVER["HTTP_USER_AGENT"]);
    if ($agent == "")
        return true;
    return preg_match("/^(curl|wget)\/.*/", $agent);
}

// TODO: disallow simultaneous deployment

function extractTo($zipFilename, $dir) {
    $zip = new ZipArchive();
    $zip->open($zipFilename);
    if (!$zip) {
        return false;
    }
    return [@$zip->extractTo($dir), $zip->numFiles];
}

function copyAll($src, $dest) {
    @mkdir($dest, 0755, true);
    $filesCopied = 0;
    foreach (
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, 
                \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST) as $item) {

        $name = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
        if ($item->isDir()) {
            @mkdir($name, 0755, true);
        } else {
            rename($item, $name);
        }
        $filesCopied++;
    }
    return $filesCopied;
}

function httpGet($url, $destFile) {
    $fileInput = fopen($url, "rb");
    $fileOutput = fopen($destFile, "w");

    if ($fileInput === FALSE) {
        throw("Failed to open stream to URL");
    }
    if ($fileOutput === FALSE) {
        throw("Failed to destination file");
    }

    $bufsize = 8192;
    while (!feof($fileInput)) {
        $contents = fread($fileInput, $bufsize);
        fwrite($fileOutput, $contents);
    }
    fclose($fileInput);
    fclose($fileOutput);
}

$pass    = trim(@$_REQUEST["pass"]); // looks for sha1(pass) in .deploy
$file    = @$_FILES["file"] ?? [];
$destDir = trim(@$_REQUEST["dir"]);
$baseDir = @$_REQUEST["base"];
$type    = @$_REQUEST["type"] ?? "local";
$url     = @$_REQUEST["remote-url"];

if (@$file["tmp_name"] || $url) {
    if (sha1($pass) != trim(@file_get_contents(".deploy-pass"))) {
        die("invalid password");
    }

    if (!$destDir)
        $destDir = ".";

    $deployZip = "deploy.zip";
    if ($url) {
        try {
            writeln("Downloading $url to $deployZip");
            httpGet($url, $deployZip);
        } catch (Exception $e) {
            writeln("failed to download remote file: $e");
        }
    } else {
        $tmp_name = $file["tmp_name"];
        move_uploaded_file($tmp_name, $deployZip);
    }

    list($okay, $numFiles) = extractTo("deploy.zip", $destDir);
    if (!$okay) {
        die("failed to extract files to $destDir, check write permissions");
    }

    writeln("$numFiles file/s extracted to $destDir/");
    if ($baseDir) {
        $dir = $destDir."/".$baseDir;
        $n = copyAll($dir, $destDir) + 1;
        writeln("$n file/s copied from $baseDir/ to $destDir/");
    }
} else if (isShellClient()) {
    echo "HTTP API parameters:\n";
    echo "\tfile=<Filename of zip to upload>\n";
    echo "\tdir=<Directory to place files>\n";
    echo "\tbase=<A directory in the zip file that will be moved to dir>\n";
    echo "\tremote-url=<A remote url that contains the zip file>\n";
    echo "Either specify url or file.\n";
} else { ?>

<form method="POST" enctype="multipart/form-data">
    <h1>Deploy files</h1>
    <fieldset>
        <legend>Upload settings</legend>
        <p>
            <label>deploy password: <input name="pass" type="password" /></label>
        <p>
            <label>destination dir: <input name="dir" /></label>
        <p>
            <label>base dir: <input name="base" /></label>
        </p>
    </fieldset>
    <fieldset>
        <legend>Zip File</legend>
        <label><input type="radio" name="type" value="local" checked> local</label>
        <input name="file" type="file">
        <br>
        <label><input type="radio" name="type" value="url"> URL</label>
        <input name="remote-url">
    </fieldset>
    </p>
        <input type="submit" value="deploy">
    </p>
</form>

<?php } ?>
