<?php

namespace Bogart;

class Debug
{
  public static function outputDebug()
  {
    $log = Log::pretty();
    $color = strstr($log, 'Error') ? 'red' : '#ddd';
    echo "<div id='bogart_debug_container' style=\"border-bottom: 2px solid {$color}; border-left: 2px solid {$color}; position: absolute; top: 0; right: 0; background-color: #eee; text-align: right; -webkit-border-bottom-left-radius: 10px; -moz-border-radius-bottomleft: 10px; border-bottom-left-radius: 10px; color: green; font-family: Arial, Helvetica Neue, Helvetica, sans-serif; font-size: 14px;\"
      >&nbsp;&#x272A; ";
    
    echo "<a href=\"javascript::void(0);\" onclick=\"this.blur();el=document.getElementById('bogart_log_container');if(el.style.display == 'block'){el.style.display = 'none';}else{el.style.display='block';}\"
         style=\"text-decoration:none; color: grey;\">&#x278A; log</a> | ";
   
   echo "<a href=\"javascript::void(0);\" onclick=\"this.blur();el=document.getElementById('bogart_timer_container');if(el.style.display == 'block'){el.style.display = 'none';}else{el.style.display='block';}\"
        style=\"text-decoration:none; color: grey;\">&#x278B; timer</a> | ";
    
    echo "<a href=\"javascript::void(0);\" onclick=\"this.blur();el=document.getElementById('bogart_config_container');if(el.style.display == 'block'){el.style.display = 'none';}else{el.style.display='block';}\"
        style=\"text-decoration:none; color: grey;\">&#x278C; config</a> | ";
    
    echo "<a href=\"javascript::void(0);\" onclick=\"this.blur();el=document.getElementById('bogart_server_container');if(el.style.display == 'block'){el.style.display = 'none';}else{el.style.display='block';}\"
        style=\"text-decoration:none; color: grey;\">&#x278D; server</a> | ";
    
    echo "<a href=\"javascript::void(0);\" onclick=\"this.blur();el=document.getElementById('bogart_request_container');if(el.style.display == 'block'){el.style.display = 'none';}else{el.style.display='block';}\"
        style=\"text-decoration:none; color: grey;\">&#x278E; request</a> | ";
    
    echo "<a href=\"javascript::void(0);\" onclick=\"el=document.getElementById('bogart_debug_container');document.body.removeChild(el);\" style=\"color: grey; text-decoration: none;\">&#x2716;</a>&nbsp;";
    
    self::outputLog();
    self::outputTimer();
    self::outputConfig();
    self::outputServer();
    self::outputRequest();
    
    echo "</div>";
  }
  
  public static function outputLog()
  {
    echo "<div id='bogart_log_container' style=\"overflow-x: scroll; width: 1000px; display: none; padding: 0 0.5em 0 1em; text-align: left; border-top: 1px solid;\"><h3>Log</h3>";
    echo Log::pretty();
    echo "</div>"; 
  }
  
  public static function outputTimer()
  {
    echo "<div id='bogart_timer_container' style=\"overflow-x: scroll; width: 1000px; display: none; padding: 0 0.5em 0 1em; text-align: left; border-top: 1px solid;\"><h3>Timer</h3>";
    echo Timer::pretty();
    echo "</div>"; 
  }
  
  public static function outputConfig()
  {
    echo "<div id='bogart_config_container' style=\"display: none; padding: 0 0.5em 1em 1em; text-align: left; border-top: 1px solid;\"><h3>Config</h3>";
    echo self::prettyPrint(Config::getAll());
    echo "</div>";
  }
  
  public static function outputServer()
  {
    echo "<div id='bogart_server_container' style=\"overflow-x: scroll; width: 1000px; display: none; padding: 0 0.5em 0 1em; text-align: left; border-top: 1px solid;\">";
    
    echo "<h3>GET</h3>";
    echo self::prettyPrint($_GET);
    
    echo "<h3>POST</h3>";
    echo self::prettyPrint($_POST);
    
    echo "<h3>FILES</h3>";
    echo self::prettyPrint($_FILES);
    
    echo "<h3>Session</h3>";
    echo self::prettyPrint($_SESSION);
    
    echo "<h3>Cookie</h3>";
    echo self::prettyPrint($_COOKIE);
    
    echo "<h3>Request</h3>";
    echo self::prettyPrint($_REQUEST);
    
    echo "<h3>Server</h3>";
    echo self::prettyPrint($_SERVER);
    //echo '<pre>'.\sfYaml::dump($_SERVER).'</pre>'; // this is kinda easier
    
    echo "<h3>Environment</h3>";
    echo self::prettyPrint($_ENV);
    
    echo "</div>";
  }
  
  public static function outputRequest()
  {
    echo "<div id='bogart_request_container' style=\"display: none; padding: 0 0.5em 1em 1em; text-align: left; border-top: 1px solid;\"><h3>Request</h3>";
    echo self::prettyPrint(Config::get('bogart.request'));
    echo "<h3>Route</h3>";
    echo self::prettyPrint(Config::get('bogart.route'));
    echo "</div>";
  }
  
  protected static function prettyPrint($array, $name = '')
  {
    echo "<div id=\"print-".$name."\" class=\"bogart-print-wrapper\">";
    foreach($array as $key => $setting)
    {
      if(is_array($setting))
      {
        echo sprintf("<b>%s</b><br />\n", strtoupper($key));
        foreach($setting as $k2 => $s2)
        {
          if(is_array($s2))
          {
            echo sprintf("<b>&nbsp;&nbsp;&#x2514; %s</b><br />\n", $k2);
            foreach($s2 as $k3 => $s3)
            {
              if(is_array($s3))
              {
                echo sprintf("<b>&nbsp;&nbsp;&nbsp;&nbsp;&#x2514; %s</b><br />\n", $k3);
                foreach($s3 as $k4 => $s4)
                {
                  if(is_object($s3) || is_array($s3))
                  {
                    echo sprintf("<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&#x2514; %s:</b> <code style=\"color:grey\">%s</code><br />\n", $k4, is_array($s3) ? stripslashes(json_encode($s3)) : "instance of ".get_class($s3));
                    continue;
                  }
                  else
                  {
                    echo sprintf("<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&#x2514; %s:</b> <code style=\"color:grey\">%s</code><br />\n", $k4, $s4?:'<em>NULL</em>');
                    continue;
                  }
                }
              }
              elseif(is_object($s3) && !method_exists($s3, '__toString'))
              {  
                echo sprintf("<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&#x2514; %s:</b><br />\n", $k4);
                echo sprintf("<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&#x2514; %s:</b> <code style=\"color:grey\">%s</code><br />\n", $k3, "instance of class ".get_class($s3));
                continue;
              }
              else
              {
                echo sprintf("<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&#x2514; %s:</b> <code style=\"color:grey\">%s</code><br />\n", $k3, $s3?:'<em>NULL</em>');
                continue;
              }
            }
          }
          else
          {
            echo sprintf("<b>&nbsp;&nbsp;&#x2514; %s:</b> <code style=\"color:grey\">%s</code><br />\n", $k2, $s2?:'<em>NULL</em>');
            continue;
          }
        }
      }
      elseif(is_scalar($setting))
      {
        echo sprintf("<b>%s:</b> <code style=\"color:grey\">%s</code><br />\n", $key, $setting);
        continue;
      }
    }
    echo "</div>";
  }
}