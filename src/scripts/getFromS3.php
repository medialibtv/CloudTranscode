<?php

/**
 * Script used to get a file in AWS S3
 **/

require __DIR__ . "/../../vendor/autoload.php";

function usage()
{
    echo("Usage: php ". basename(__FILE__) . " [-h] [--force] --bucket <s3 bucket> --file <filename> --to <filepath>\n");
    echo("--help, -h: Print this help\n");
    echo("--bucket <s3 bucket>: Name of the S3 bucket\n");
    echo("--file <filename>: Name of the file in the S3 bucket\n");
    echo("--to <filepath>: Full path to file where to save. You can override original filename.\n");
    echo("--force: Force download even if file exists locally\n\n");
    exit(0);
}

function check_input_parameters($options)
{
    if (!count($options) || isset($options['h']) ||
        isset($options['help']))
        usage();

    if (!isset($options['bucket']) || !isset($options['file']) ||
        !isset($options['to']))
        throw new \SA\CpeSdk\CpeException("Missing mandatory parameter!");
}

$options = getopt("h", array("bucket:", "file:", "to:", "force::", "help::"));
check_input_parameters($options);

// If local file already exists. We don't download unless --force
if (!isset($options['force']) &&
    file_exists($options['to']) &&
    filesize($options['to']))
{
    $out = [ "status" => "SUCCESS",
             "msg" => "[".__FILE__."] Using local copy: '" . $options['to']  . "'" ];
    print json_encode($out)."\n";
    exit(0);
}

if (!($region = getenv("AWS_STORAGE_REGION")))
    throw new CpeSdk\CpeException("Set 'AWS_STORAGE_REGION' environment variable!");

$endpoint = getenv("AWS_STORAGE_ENDPOINT");

if (!($accessKeyId = getenv("AWS_STORAGE_ACCESS_KEY_ID")))
    throw new CpeSdk\CpeException("Set 'AWS_STORAGE_ACCESS_KEY_ID' environment variable!");

if (!($accessSecret = getenv("AWS_STORAGE_SECRET_ACCESS_KEY")))
    throw new CpeSdk\CpeException("Set 'AWS_STORAGE_SECRET_ACCESS_KEY' environment variable!");

$s3 = new \Aws\S3\S3Client([
    "version" => "latest",
    "region"  => $region,
    "credentials" => [
        "key" => $accessKeyId,
        "secret" => $accessSecret
    ],
    "use_path_style_endpoint" => $endpoint ? true : false,
    "endpoint" => $endpoint
]);

// Download and Save object to a local file.
$res = $s3->getObject(array(
    'Bucket' => $options['bucket'],
    'Key'    => ltrim($options['file'], '/'),
    'SaveAs' => $options['to']
));

$out = [ "status" => "SUCCESS",
         "msg" => "[".__FILE__."] Download '" . $options['bucket'] . "/" . $options['file'] . "' successful !" ];

print json_encode($out)."\n";
