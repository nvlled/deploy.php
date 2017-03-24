<?php

// TODO: disallow simultaneous deployment

function extractTo($zipFilename, $dir) {
    $zip = new ZipArchive();
    $zip->open($zipFilename);
    if (!$zip) {
        return false;
    }
    return @$zip->extractTo($dir);
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
            echo "creating directory $name<br>";
        } else {
            $filesCopied++;
            rename($item, $name);
            echo "copying $item to $name<br>";
        }
    }
    echo "$filesCopied file/s copied<br>";
}

$pass    = trim(@$_REQUEST["pass"]); // looks for sha1(pass) in .deploy
$file    = @$_FILES["file"] ?? [];
$destDir = trim(@$_REQUEST["dir"]);
$baseDir = @$_REQUEST["base"];

if (@$file["tmp_name"]) {
    if (sha1($pass) != trim(@file_get_contents(".deploy-pass"))) {
        die("invalid password");
    }

    if (!$destDir)
        $destDir = ".";

    move_uploaded_file($file["tmp_name"], "deploy.zip");
    echo "*dir: $destDir<br>";

    $okay = extractTo("deploy.zip", $destDir);
    if (!$okay) {
        die("failed to upload file, check write permissions");
    }

    if ($baseDir) {
        $dir = $destDir."/".$baseDir;
        copyAll($dir, $destDir);
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <p>
        deploy password: <input name="pass" type="password" />
    <p>
        destination dir: <input name="dir" />
    <p>
        base dir: <input name="base" />
    <p>
        zip file: <input name="file" type="file">
        <input type="submit" value="deploy">
    </p>
</form>

