<?php

class Kontrol
   {
   public static function capture($type,$event)
      {
      Kontrol::preprocess($type,$event,is_a($event,'Exception') ? NULL : debug_backtrace());
      }

   public static function critical($event)
      {
      Kontrol::preprocess('Critical',$event,is_a($event,'Exception') ? NULL : debug_backtrace());
      }

   public static function database($event)
      {
      Kontrol::preprocess('Database',$event,is_a($event,'Exception') ? NULL : debug_backtrace());
      }

   public static function debug($event)
      {
      Kontrol::preprocess('Debug',$event,is_a($event,'Exception') ? NULL : debug_backtrace());
      }

   public static function error($event)
      {
      Kontrol::preprocess('Error',$event,is_a($event,'Exception') ? NULL : debug_backtrace());
      }

   public static function exception(Exception $e)
      {
      if(!Kontrol::isInitialized()) return;
      if(!Kontrol::$Variables['Enabled']) return;
      Kontrol::process('Exception',0,$e->getMessage(),$e->getFile(),$e->getLine(),$e->getTrace());
      }

   public static function feedback($message)
      {
      if(!Kontrol::isInitialized()) return;
      if(!Kontrol::$Variables['Enabled']) return;
      Kontrol::process('Feedback',0,$message);
      }

   public static function info($event)
      {
      Kontrol::preprocess('Info',$event,is_a($event,'Exception') ? NULL : debug_backtrace());
      }

   public static function initialize($APIKey,$Version = '1.0')
      {
      if(defined('DISABLE_KONTROL') && constant('DISABLE_KONTROL') !== false) return;

      Kontrol::$Variables['APIKey'] = $APIKey;
      Kontrol::$Variables['Version'] = $Version;
      Kontrol::setEnabled(true);
      Kontrol::setErrorLogEnabled(false);
      Kontrol::setLocalLogEnabled(false);
      Kontrol::setOnErrorRedirect(false);
      Kontrol::setPHPEnvironmentEnabled(false);
      Kontrol::setRemoteLogEnabled(true);
      Kontrol::setRuntimePropertiesEnabled(false);
      Kontrol::setStrictEnabled(false);
      Kontrol::_errorSeverity(0);

      date_default_timezone_set('UTC');

      assert_options(ASSERT_ACTIVE,1);
      assert_options(ASSERT_WARNING,0);
      assert_options(ASSERT_BAIL,0);
      assert_options(ASSERT_QUIET_EVAL,1);

      error_reporting(~0 & ~E_STRICT);
      set_error_handler('Kontrol::_error');
      set_exception_handler('Kontrol::exception');
      register_shutdown_function('Kontrol::_shutdown');
      assert_options(ASSERT_CALLBACK,'Kontrol::_assert');

      ini_set('display_errors',false);
      }

   public static function isInitialized()
      {
      return isset(Kontrol::$Variables['APIKey']);
      }

   public static function setErrorLogEnabled($enabled)
      {
      Kontrol::$Variables['EnableErrorLog'] = $enabled === true;
      }

   public static function setLocalLogEnabled($enabled,$logFile = NULL)
      {
      Kontrol::$Variables['EnableLocalLog'] = $enabled === true;
      if($logFile == NULL) $logFile = $_SERVER['DOCUMENT_ROOT'] . '/kontrol.log';
      Kontrol::$Variables['LocalLogFile'] = $logFile;
      }

   public static function setOnErrorRedirect($URL = false)
      {
      Kontrol::$Variables['OnErrorRedirect'] = $URL;
      }

   public static function setPHPEnvironmentEnabled($enabled)
      {
      Kontrol::$Variables['EnablePHPEnvironment'] = $enabled === true;
      }

   public static function setRemoteLogEnabled($enabled)
      {
      Kontrol::$Variables['EnableRemoteLog'] = $enabled === true;
      }

   public static function setRuntimePropertiesEnabled($enabled)
      {
      Kontrol::$Variables['EnableRuntimeProperties'] = $enabled === true;
      }

   public static function setStrictEnabled($enabled)
      {
      error_reporting($enabled===true ? ~0 : ~0 & ~E_STRICT);
      Kontrol::$Variables['EnableStrict'] = $enabled === true;
      }

   public static function warning($event)
      {
      Kontrol::preprocess('Warning',$event,is_a($event,'Exception') ? NULL : debug_backtrace());
      }

   public static function _assert($file,$line,$message)
      {
      if(!Kontrol::isInitialized()) return;
      if(!Kontrol::$Variables['Enabled']) return;
      $context = debug_backtrace();
      Kontrol::process('Assert',0,$message,$file,$line,$context);
      }

   public static function _error($level,$message,$file,$line)
      {
      if(!Kontrol::isInitialized()) return false;
      if(!Kontrol::$Variables['Enabled']) return true;
      $DisableDefaultHandler = true;
      if($level == 0) return $DisableDefaultHandler;
      if(!(error_reporting() & $level)) return $DisableDefaultHandler;
      if(Kontrol::errorSeverity($level) <= Kontrol::$Variables['Severity']) return $DisableDefaultHandler;
      if(!Kontrol::$Variables['EnableStrict'] && $level == E_STRICT) return $DisableDefaultHandler;
      $type = Kontrol::errorType($level);
      Kontrol::process($type,$level,$message,$file,$line);
      return $DisableDefaultHandler;
      }

   public static function _errorSeverity($severity)
      {
      Kontrol::$Variables['Severity'] = $severity;
      }

   public static function _shutdown()
      {
      if(!Kontrol::isInitialized()) return;
      if(!Kontrol::$Variables['Enabled']) return;
      if(function_exists('error_get_last'))
         {
         $e = error_get_last();
         if(!is_null($e) && isset($e['type']))
            {
            if(!(error_reporting() & $e['type'])) return;
            if(!Kontrol::$Variables['EnableStrict'] && $e['type'] == E_STRICT) return;
            $type = Kontrol::errorType($e['type']);
            Kontrol::process($type,$e['type'],$e['message'],$e['file'],$e['line']);
            }
         }
      }

   private static function errorList()
      {
      return Array(0 => Array('E_EXCEPTION','Exception',1),1 => Array('E_ERROR','Error',3),2 => Array('E_WARNING','Warning',2),4 => Array('E_PARSE','Error',3),8 => Array('E_NOTICE','Notice',1),16 => Array('E_CORE_ERROR','Error',3),32 => Array('E_CORE_WARNING','Warning',2),64 => Array('E_COMPILE_ERROR','Error',3),128 => Array('E_COMPILE_WARNING','Warning',2),256 => Array('E_USER_ERROR','Error',3),512 => Array('E_USER_WARNING','Warning',2),1024 => Array('E_USER_NOTICE','Notice',1),2048 => Array('E_STRICT','Strict',1),4096 => Array('E_RECOVERABLE_ERROR','Error',1),8192 => Array('E_DEPRECATED','Deprecated',1),16384 => Array('E_USER_DEPRECATED','Deprecated',1));
      }

   private static function errorLog($type,$event)
      {
      $date = date('Y-m-d H:i:s');
      error_log("[$date] [$type] $event"); // . PHP_EOL
      }

   private static function errorName($code)
      {
      $list = Kontrol::errorList();
      return isset($list[$code]) ? $list[$code][0] : 'Unknown';
      }

   private static function errorSeverity($code)
      {
      $list = Kontrol::errorList();
      return isset($list[$code]) ? $list[$code][2] : 3;
      }

   private static function errorType($code)
      {
      $list = Kontrol::errorList();
      return isset($list[$code]) ? $list[$code][1] : 'Unknown';
      }

   private static function localLog($type,$event)
      {
      if($f = @fopen(Kontrol::$Variables['LocalLogFile'],'a+'))
         {
         $date = date('Y-m-d H:i:s');
         fwrite($f,"[$date] [$type] $event" . PHP_EOL);
         fclose($f);
         }
      }

   private static function preprocess($type,$e,$context = NULL)
      {
      if(!Kontrol::isInitialized()) return;
      if(!Kontrol::$Variables['Enabled']) return;
      if($context != NULL)
         Kontrol::process($type,0,$e,$context[0]['file'],$context[0]['line'],$context);
      else
         Kontrol::process($type,0,$e->getMessage(),$e->getFile(),$e->getLine(),$e->getTrace());
      }

   private static function process($type,$level,$message,$file = NULL,$line = NULL,$context = NULL,$opaque = NULL)
      {
      $event = !empty($file) && !empty($line) ? $message . PHP_EOL . '   in file ' . $file . PHP_EOL . '   on line ' . $line : $message;

      if(Kontrol::$Variables['EnableErrorLog']) Kontrol::errorLog($type,$event);
      if(Kontrol::$Variables['EnableLocalLog']) Kontrol::localLog($type,$event);
      if(Kontrol::$Variables['EnableRemoteLog']) Kontrol::remoteLog($type,$level,$message,$file,$line,$context,$event,$opaque);

      if(Kontrol::errorSeverity($level) == 3 || $type == 'Critical' || $type == 'Database')
         {
         if(Kontrol::$Variables['OnErrorRedirect'] !== false)
            {
            Kontrol::setEnabled(false);
            $URL = Kontrol::$Variables['OnErrorRedirect'];
            if(!headers_sent()) header('Location: ' . $URL);
            else echo
               '<html><head>',
               '<script type="text/javascript">window.location.replace("', $URL, '");</script>',
               '<noscript><meta http-equiv="refresh" content="0;url=', $URL, '" /></noscript>',
               '</head><body></body></html>';
            }
         else if(!headers_sent()) header("HTTP/1.0 200 OK");
         exit();
         }
      }

   private static function remoteLog($type,$level,$message,$file,$line,$context,$event,$opaque)
      {
      $extra = Array();
      if($level != 0) $extra['Level'] = $level;
      if(!empty($message)) $extra['Message'] = $message;
      if(!empty($file)) $extra['File'] = $file;
      if(!empty($line)) $extra['Line'] = $line;
      if($context != NULL) $extra['Context'] = $context;

      if(Kontrol::$Variables['EnableRuntimeProperties'])
         {
         if(isset($_COOKIE)) $extra['Cookie'] = $_COOKIE;
         if(isset($_FILES)) $extra['Files'] = $_FILES;
         if(isset($_GET)) $extra['Get'] = $_GET;
         if(isset($_POST)) $extra['Post'] = $_POST;
         if(isset($_REQUEST)) $extra['Request'] = $_REQUEST;
         if(isset($_SERVER)) $extra['Server'] = $_SERVER;
         if(isset($_SESSION)) $extra['Session'] = $_SESSION;

         $inc_files = get_included_files();
         if(count($inc_files)) $extra['Included Files'] = $inc_files;
         $extra['Include Path'] = get_include_path();
         $extra['Response Headers'] = headers_list();
         }
      if(Kontrol::$Variables['EnablePHPEnvironment'])
         {
         if(function_exists('php_ini_scanned_files') && function_exists('php_ini_loaded_file'))
            {
            $ini = php_ini_scanned_files();
            $ini = str_replace(' ,',',',str_replace(', ',',',str_replace("\n",'',$ini)));
            $ini = $ini != '' ? explode(',',$ini) : Array();
            $ini[] = php_ini_loaded_file();
            $extra['INI Files'] = $ini;
            }

         $extra['Extensions'] = get_loaded_extensions();
         sort($extra['Extensions']);
         $extra['INI Settings'] = ini_get_all();
         $extra['PHPVersion'] = phpversion();
         $extra['SAPI'] = php_sapi_name();
         $extra['System'] = php_uname();
         }

      $request = Array();
      $request['Time'] = time();
      $request['A'] = Kontrol::$Variables['APIKey'];
      $request['P'] = 7;
      $request['V'] = Kontrol::$Variables['Version'];
      $request['T'] = $type;
      $request['E'] = $event;
      $request['X'] = json_encode($extra);
      $request['Z'] = Kontrol::$API_VERSION;
      if($opaque != NULL) $request['O'] = $opaque;

      $ch = curl_init();
      curl_setopt($ch,CURLOPT_CAINFO,dirname(__FILE__) . '/bundle.crt');
      curl_setopt($ch,CURLOPT_FORBID_REUSE,true);
      curl_setopt($ch,CURLOPT_FRESH_CONNECT,true);
      curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);
      curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,true);
      curl_setopt($ch,CURLOPT_URL,'https://api.kontrol.io/rest/event-add.jsp');
      curl_setopt($ch,CURLOPT_POST,1);
      curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($request));
      curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
      curl_exec($ch);
      curl_close($ch);
      }

   private static function setEnabled($enabled)
      {
      Kontrol::$Variables['Enabled'] = $enabled === true;
      }

   private static $API_VERSION = 2;
   private static $Variables = Array();
   }

?>