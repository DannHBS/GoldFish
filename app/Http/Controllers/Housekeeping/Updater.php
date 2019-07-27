<?php
namespace App\Http\Controllers\Housekeeping;

use App\Http\Controllers\Controller;
use Request;
use App\Helpers\CMS;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Updater extends Controller
{
  public function check()
  {
    if(CMS::fuseRights('updater')){
        $url="http://layne.cf/goldfish/updates/laraupdater.json";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $result=curl_exec($ch);
        curl_close($ch);
        $json = json_decode($result, true);
        if (version_compare(config('app.version_number'),$json['version'],'!=')) {
            return $json['version'];
        }
        return null;
    }
    else {
      return null;
    }
  }
  public function update() {
    if(CMS::fuseRights('updater')){
        $url="http://layne.cf/goldfish/updates/laraupdater.json";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $result=curl_exec($ch);
        curl_close($ch);
        $json = json_decode($result, true);
        if (version_compare(config('app.version_number'),$json['version'],'!=')) {
          $sqlLink = null;
          if($json['sql'] == 1){
            $sqlLink = $json['sqlLink'];
          }
          self::extractZip('http://layne.cf/goldfish/updates/'.$json['zipName']);
          $arr = array(
            "version" => $json['version'],
            "message" => "Updated!",
            "link" => $sqlLink,
            "zip" => $json['zipName']
          );
          $myJSON = json_encode($arr);
          return response($myJSON, 200)->header('Content-Type', 'application/json');
        }
    }else {
        return null;
    } 
  }
  public function extractZip($zip) {
    $url = $zip;
    $zipFile = public_path()."\install\update.zip"; // Local Zip File Path
    $zipResource = fopen($zipFile, "w");
    // Get The Zip File From Server
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER,true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
    curl_setopt($ch, CURLOPT_FILE, $zipResource);
    $page = curl_exec($ch);
    curl_close($ch);
    $zip = new \ZipArchive;
    if($zip->open($zipFile) != "true"){
    echo "Error :- Unable to open the Zip File";
    } 
    /* Extract Zip File */
    $zip->extractTo(base_path());
    $zip->close();
  }
}