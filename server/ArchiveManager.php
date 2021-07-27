<?php



class ArchiveManager
{

    private $langs;

    /**
     * @param Array $langs a list of supported languages passed as array
     */
    public function __construct(array $langs)
    {
        $this->langs =  $langs;
    }
    /**
     * @param String $ext the file extention
     * to determine its language
     * @return String|null the language or null if not found
     */
    public   function getExtension($ext = 'html', $reverse = false)
    {

        if (!$reverse) :
            foreach ($this->langs as $lang => $exts) :

                if ($this->contains($exts, '|')) {
                    $extesions  = explode('|', $exts);
                    foreach ($extesions as  $x)
                        if ($x  === $ext)
                            return  $lang;
                } elseif ($exts === $ext)
                    return $lang;

            endforeach;
        else :
            if (isset($langs[$ext]))
                return  explode('|', $langs[$ext])[0];
        endif;

        return null;
    }


    public  function downloadZipItem($zipName, $index)
    {
        $path =  __DIR__ . '/files/' .  $zipName;
        $content =  null;
        $filename = '';
        $zip = new ZipArchive();
        if ($zip->open($path) == TRUE) {
            $content  =  $zip->getFromIndex($index);
            $filename = $zip->statIndex($index)['name'];
            $zip->close();
        }
        return  ['content' => $content, 'fileName' => $filename];
    }


    /**
     * @param String $path the zip file path to unzip
     * @return string a list of zip content encoded as html
     */
    public  function unzip($path)
    {
        if ($path ===  null) return 'File not found.';
        $info = [];
        $zip = new ZipArchive();
        $zipName  = pathinfo($path, PATHINFO_FILENAME);
        $entryList  = '<ul class="list-group content-list "  data-entry="' . $zipName . '">';
        if ($zip->open($path) == TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++)
                $entryList .= $this->list($zip, $i, $info);
            $zip->close();
        }
        return  ['content' => $entryList . '</ul>', 'info' => $info];
    }


    private   function list($zip, $index, &$info)
    {
        $list = '';

        //set the prerequisit inputs
        $stat =  $zip->statIndex($index); //an array of file statistics or details
        $filename =  $stat['name']; // entry name
        $size =  $stat['size']; //entry size


        $isFile  =  strstr($filename, '/') === false;
        $anyFile  = (preg_match('/(\..+)$/', $filename) && $size > 0) || $size > 0;
        $type  = $anyFile ? 'file' : 'folder';
        $icon = ($type === 'file'  ? ' fa-file text-info' : ' fa-folder text-warning');
        $plus  =  ($type === 'folder' ? '<i class="fa fa-plus" ></i>'  : '');
        $x =  substr($filename, 0, strpos($filename, '/'));


        if (($size === 0 && $x . '/' === $filename) || $isFile) {

            $info[$index] =
                ['type' => $type, 'path' => $filename, 'size' => $size, 'open' => 'false'];



            $x = str_replace('/', '', $filename);
            $data  = 'data-index="' . $index . '" data-open="false" data-type="' . $type . '" data-path="' . $filename . '"';
            $list .= '<li  class="list-group-item py-1 index-' . $index . '" ' . $data . ' id="' . $index . '">';
            $list .=  $plus . '<i class="fa ' . $icon . '  "></i>';
            $list .= '<a  class="btn btn-link shadow-none ">' . $x . '</a>';
            if ($type === 'file')
                $list .= '<span class="position-absolute file-size small">' . $this->formatDataUnit($size) . '</span>';
            $list .= '</li>';
        }


        return $list;
    }


    public  function getzipEntryContent($zipName, $index, $type, $fName, $fPath)
    {
        $_path = $fPath;
        $fPath =  $this->beforeLast($fPath, '/');
        
        $info = [];
        $path =  __DIR__ . '/files/' .  $zipName;
        $entryList  = '<ul class="list-group content-list "  data-entry="' . $zipName . '">';
        $zip = new ZipArchive();
        $content  =  '';
        $fileType = '';
        $validEncoding = false;
        if ($zip->open($path) == TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat =  $zip->statIndex($i);
                $thisName =  $stat['name'];
                if ($i == $index) {
                    $index =  $i;
                    if ($type === 'file') {
                        $content =  $zip->getFromIndex($index);

                        $fileType =  pathinfo($fName, PATHINFO_EXTENSION);
                        $validEncoding =   (mb_check_encoding($content, 'utf-8')  ? true : false);

                        $info['fileType'] = $this->getExtension($fileType);
                        $info['name'] =  $thisName;
                        $info['type'] = $type;
                        $info['path'] =$_path;
                        $info['isImage'] = false;

                        if (preg_match('/(jpg|jpeg|png|gif)/', $fileType)) {
                            $content =  "data:image/$fileType;base64," . base64_encode($content);
                            $info['fileType'] = $fileType;
                            $info['isImage'] =  true;
                        } else if ($validEncoding === false) {
                            $content = null;
                        }else{
                            if($info['fileType'] == null)
                            $info['fileType'] = 'text';
                        }
                        break;
                    }
                }

                if ($type === 'folder') {
                    $index =  $i;
                    $dir  = substr($thisName, 0, strlen($fPath));
                    if ($dir ===  $fPath && $thisName !== $fPath . '/') {
                        $size =  $stat['size'];
                        $isFile  =  $this->after($thisName, $dir . '/');
                        $isFile =  $this->contains($isFile, '/') === false && $size > 0;
                        $anyFile  = (preg_match('/(\..+)$/', $thisName) && $size > 0) || $size > 0;
                        $thisType  = $anyFile ? 'file' : 'folder';
                        $icon = ($anyFile  ? 'fa-file text-info' : 'fa-folder text-warning');
                        $plus  =  $thisType === 'folder'  ? ' <i class="fa fa-plus " ></i> '  : '';
                        $thisFolder = $this->thisFolder($thisName, $fPath, $size, $dir);

                        if ($thisFolder  || $isFile) {


                            $thisName =  $this->after($thisName, $dir . '/');
                            $x = str_replace('/', '', $thisName);
                            $thisPath  = $dir . '/' . $thisName;
                            $info[$i] = ['type' => $thisType, 'size' => $size, 'path' => $thisPath, 'open' => 'false'];

                            $data  = 'data-index="' . $i . '" data-open="false" data-type="' . $thisType . '" data-path="' . $thisPath . '"';
                            $entryList  .= '<li class="list-group-item py-1 index-' . $index . '" ' . $data . '>';
                            $entryList  .=  $plus . '<i class="fa ' . $icon . '  "></i>';
                            $entryList  .= '<a  class="btn btn-link shadow-none ">' . $x . '</a>';
                            if ($thisType  === 'file')
                            $entryList  .= '<span class="position-absolute file-size small">' . $this->formatDataUnit($size) . '</span>';
                            $entryList .= '</li>';
                        }
                    }
                }
            }

            $zip->close();
        } //open zip

        if ($type == 'folder')
            $content = $entryList . '</ul>';

        if (preg_match('/(\..+?)$/', $fPath) == false) {
            $fPath = $fPath . '/' . $fName;
            if ($this->startsWith($fPath, '/'))
                $info[$index]['name']  = $this->after($fPath, '/');
            else
                $info[$index]['name']  = $fPath;
        }

        $info[$index]['isText'] = $validEncoding;


        return  ['content'=>$content, 'info'=>$info];
    }

    private  function thisFolder($thisName, $fPath, $size)
    {
        $thisName =    $this->after($thisName, $fPath . '/');
        $t =   $this->substrCount($thisName, '/', 1);
        return  $size === 0 &&  $t === 1;
    }

    public  function beforeLast($haystack, $needle)
    {
        return substr($haystack, 0, strrpos($haystack, $needle));
    }

    public  function after($string, $substring)
    {

        $pos = strpos($string, $substring);
        if ($pos === false) {
            return $string;
        } else {
            return substr($string, $pos + strlen($substring));
        }
    }

    public  function substrCount($haystack, $needle)
    {
        return  substr_count($haystack, $needle);
    }

    public  function contains($haystack, $needle)
    {
        return  strstr($haystack, $needle) !== false;
    }

    public  function startsWith($haystack, $needle)
    {
        return  strpos($haystack, $needle) === 0;
    }


    /**
     * This function abbreviates a file size numbers
     * into MB ,KB and GB
     * @param $size  the size of data to abbreviate
     */
    public      function formatDataUnit($size)
    {
        $M = 1024;
        $p = 1;
        $kb = $size / $M;
        $mb = $kb / $M;
        $gb = $mb / $M;

        if ($size < $M) {
            return $size . ' Bytes';
        } else if ($kb < $M) {
            return number_format((float) $kb, $p, '.', '') . ' KB';
        } else if ($mb < $M) {
            return number_format((float) $mb, $p, '.', '') . ' MB';
        } else if ($gb < $M) {
            return number_format((float) $gb, $p, '.', '') . ' GB';
        }
    }
}
