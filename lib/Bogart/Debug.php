<?php

namespace Bogart;

class Debug
{
  public static function outputDebug()
  {
    if(Config::enabled('timer')) Timer::write('Debug::outputDebug', true);
    
    if(Config::enabled('log'))
    {
      $log_output = Log::pretty();
      $log_count = Log::$count;
      $color = strstr($log_output, 'Error') ? 'red' : '#ddd';
    }
    
    if(Config::enabled('timer'))
    {
      $timers = \sfTimerManager::getTimers();
      $total_time = $timers['App'] ? sprintf("%dms", $timers['App']->getElapsedTime() * 1000) : null;
    }
    
    if(Config::enabled('log'))
    {
      $query_count = Store::count('query_log', array(
          'request_id' => Request::$id
          ));
    
      $queries = Store::find('query_log', array(
          'request_id' => Request::$id
          ));
    
      $profile_count = Store::count('system.profile', array('ts' => array('$gt' => new \MongoDate($_SERVER['REQUEST_TIME']))));
    
      $profile = Store::find('system.profile', array('ts' => array('$gt' => new \MongoDate($_SERVER['REQUEST_TIME']))));
    }
    
    $request_url = Config::get('bogart.request.path');
    
    echo "<div id='bogart_debug_container' style=\"border-bottom: 2px solid {$color}; border-left: 2px solid {$color}; position: absolute; top: 0; right: 0; background-color: #eee; text-align: right; -webkit-border-bottom-left-radius: 10px; -moz-border-radius-bottomleft: 10px; border-bottom-left-radius: 10px; color: green; font-family: Arial, Helvetica Neue, Helvetica, sans-serif; font-size: 14px;\"
      >&nbsp;&#x272A; ";
    
    if(Config::enabled('log'))
    {
      echo "<a href=\"javascript::void(0);\" onclick=\"this.blur();el=document.getElementById('bogart_log_container');if(el.style.display == 'block'){el.style.display = 'none';}else{el.style.display='block';}\"
           style=\"text-decoration:none; color: grey;\">&#x278A; log ($log_count)</a> | ";
    }
    
    if(Config::enabled('timer'))
    {
     echo "<a href=\"javascript::void(0);\" onclick=\"this.blur();el=document.getElementById('bogart_timer_container');if(el.style.display == 'block'){el.style.display = 'none';}else{el.style.display='block';}\"
          style=\"text-decoration:none; color: grey;\">&#x278B; timer ($total_time)</a> | ";
    }
    
    echo "<a href=\"javascript::void(0);\" onclick=\"this.blur();el=document.getElementById('bogart_config_container');if(el.style.display == 'block'){el.style.display = 'none';}else{el.style.display='block';}\"
        style=\"text-decoration:none; color: grey;\">&#x278C; config</a> | ";
    
    echo "<a href=\"javascript::void(0);\" onclick=\"this.blur();el=document.getElementById('bogart_server_container');if(el.style.display == 'block'){el.style.display = 'none';}else{el.style.display='block';}\"
        style=\"text-decoration:none; color: grey;\">&#x278D; server ({$_SERVER['HTTP_HOST']})</a> | ";
    
    echo "<a href=\"javascript::void(0);\" onclick=\"this.blur();el=document.getElementById('bogart_request_container');if(el.style.display == 'block'){el.style.display = 'none';}else{el.style.display='block';}\"
        style=\"text-decoration:none; color: grey;\">&#x278E; request ($request_url)</a> | ";
    
    if(Config::enabled('log'))
    {
      echo "<a href=\"javascript::void(0);\" onclick=\"this.blur();el=document.getElementById('bogart_store_container');if(el.style.display == 'block'){el.style.display = 'none';}else{el.style.display='block';}\"
          style=\"text-decoration:none; color: grey;\">&#x278F; store ($query_count/$profile_count)</a> | ";
    }
    
    echo "<a href=\"javascript::void(0);\" onclick=\"el=document.getElementById('bogart_debug_container');document.body.removeChild(el);\" style=\"color: grey; text-decoration: none;\">&#x2716;</a>&nbsp;";
    
    if(Config::enabled('log')) self::outputLog($log_output, $log_count);
    if(Config::enabled('timer')) self::outputTimer($total_time);
    self::outputConfig();
    self::outputServer();
    self::outputRequest();
    if(Config::enabled('log')) self::outputStore($queries, $query_count, $profile, $profile_count);
    
    echo "</div>";
    
    if(Config::enabled('timer')) Timer::write('Debug::outputDebug');
  }
  
  public static function outputLog($log_output, $total = 0)
  {
    echo "<div id='bogart_log_container' style=\"overflow-x: scroll; width: 1000px; display: none; padding: 0 0.5em 0 1em; text-align: left; border-top: 1px solid;\"><h3>Log ($total)</h3>";
    echo $log_output;
    echo "</div>"; 
  }
  
  public static function outputTimer($total = 0)
  {
    echo "<div id='bogart_timer_container' style=\"overflow-x: scroll; width: 1000px; display: none; padding: 0 0.5em 0 1em; text-align: left; border-top: 1px solid;\"><h3>Timer ($total)</h3>";
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
    
    if(Config::enabled('sessions') && isset($_SESSION))
    {
      echo "<h3>Session</h3>";
      echo self::prettyPrint($_SESSION);
    }
    
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
    echo "<div id='bogart_request_container' style=\"display: none; padding: 0 0.5em 1em 1em; text-align: left; border-top: 1px solid;\">";
    echo "<h3>User</h3>";
    echo self::prettyPrint(Config::get('bogart.user'));
    echo "<h3>Request</h3>";
    echo self::prettyPrint(Config::get('bogart.request'));
    echo "<h3>Route</h3>";
    echo self::prettyPrint(Config::get('bogart.route'));
    echo "<h3>View</h3>";
    echo self::prettyPrint(Config::get('bogart.view'));
    echo "</div>";
  }
  
  public static function outputStore($queries, $queries_count = 0, $profile, $profile_count = 0)
  {
    echo "<div id='bogart_store_container' style=\"display: none; padding: 0 0.5em 1em 1em; text-align: left; border-top: 1px solid;\"><h3>Store ($queries_count/$profile_count)</h3>";
    
    echo "<h3>Stats</h3>";
    $data = Store::dbstats();
    echo self::prettyPrint($data);
    
    echo "<h3>Query Log ($queries_count)</h3>";
    echo "<p><em>Not including logging.</em></p>";
    
    $total_time = 0;
    $total_queries = array('insert' => 0, 'find' => 0, 'update' => 0, 'findOne' => 0, 'count' => 0, 'remove' => 0);
    ?>
      <table>
        <tr>
          <th>#</th>
          <th>time</th>
          <th>type</th>
          <th>collection</th>
          <th>query</th>
          <th>elapsed_time</th>
          <th>safe</th>
        </tr>
        <?php $i=0; foreach($queries as $query){
          $total_time += $query['elapsed_time'];
          $total_queries[$query['type']] += 1;
          $i++;
          ?>
          <tr style="<?php echo $query['elapsed_time'] > 1000 ? 'color:red;' : null ?>">
            <td><?php echo $i ?></td>
            <td><?php echo date('h:i:s', $query['time']->sec) ?></td>
            <td><?php echo $query['type'] ?></td>
            <td><?php echo $query['collection'] ?></td>
            <td><?php echo isset($query['query']) ? '<pre>'.print_r($query['query'], true).'</pre>' : '-' ?></td>
            <td><?php echo sprintf('%0.5f', $query['elapsed_time']*1000) ?> ms</td>
            <td><?php echo isset($query['safe']) ? (int) $query['safe'] : '-' ?></td>
          </tr>
        <?php } ?>
        <tr>
          <td colspan="5" align="right" style="border-top: 1px solid green;">elapsed time</td>
          <td colspan="2" style="border-top: 1px solid green;"><?php echo sprintf('%0.5f', $total_time*1000) ?> ms</td>
        </tr>
        <tr>
          <td colspan="5" align="right">insert</td>
          <td><?php echo $total_queries['insert'] ?></td>
        </tr>
        <tr>
          <td colspan="5" align="right">find</td>
          <td><?php echo $total_queries['find'] ?></td>
        </tr>
        <tr>
          <td colspan="5" align="right">findOne</td>
          <td><?php echo $total_queries['findOne'] ?></td>
        </tr>
        <tr>
          <td colspan="5" align="right">update</td>
          <td><?php echo $total_queries['update'] ?></td>
        </tr>
        <tr>
          <td colspan="5" align="right">count</td>
          <td><?php echo $total_queries['count'] ?></td>
        </tr>
        <tr>
          <td colspan="5" align="right">remove</td>
          <td><?php echo $total_queries['remove'] ?></td>
        </tr>
      </table>
    <?php
    
    echo "<h3>Profile ($profile_count)</h3>";
    echo "<p><em>Since initial request time.</em></p>";
    
    $total_time = 0;
    $total_queries = 0;
    ?>
      <table>
        <tr>
          <th>#</th>
          <th>time</th>
          <th>info</th>
          <th>elapsed_time</th>
        </tr>
        <?php $i=0; foreach($profile as $query){
          $total_time += $query['millis'];
          $total_queries++;
          $i++;
          ?>
          <tr style="<?php echo $query['millis'] > 1000 ? 'color:red;' : null ?>">
            <td><?php echo $i ?></td>
            <td><?php echo date('h:i:s', $query['ts']->sec) ?></td>
            <td><?php echo $query['info'] ?></td>
            <td><?php echo $query['millis'] ?> ms</td>
          </tr>
        <?php } ?>
        <tr>
          <td colspan="5" align="right" style="border-top: 1px solid green;">elapsed time</td>
          <td colspan="2" style="border-top: 1px solid green;"><?php echo $total_time ?> ms</td>
        </tr>
        <tr>
          <td colspan="5" align="right">queries</td>
          <td><?php echo $total_queries ?></td>
        </tr>
      </table>
    <?php
    
    echo "</div>";
  }
  
  protected static function prettyPrint($array, $name = '')
  {
    $out = "<div id=\"print-".$name."\" class=\"bogart-print-wrapper\">";
    if($array)
    {
      foreach($array as $key => $setting)
      {
        if(is_array($setting))
        {
          $out .= sprintf("<b>%s</b><br />\n", $key);
          foreach($setting as $k2 => $s2)
          {
            if(is_array($s2))
            {
              if(empty($s2))
              {
                $out .= sprintf("<b>&nbsp;&nbsp;&nbsp;&nbsp;&#x2514; %s:</b> <code style=\"color:grey\">%s</code><br />\n", $k2, '<em>array(NULL)</em>');
                continue;
              }
              
              $out .= sprintf("<b>&nbsp;&nbsp;&#x2514; %s</b><br />\n", $k2);
              foreach($s2 as $k3 => $s3)
              {
                if(is_array($s3))
                {
                  if(empty($s3))
                  {
                    $out .= sprintf("<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&#x2514; %s:</b> <code style=\"color:grey\">%s</code><br />\n", $k3, '<em>array(NULL)</em>');
                    continue;
                  }
                  
                  $out .= sprintf("<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&#x2514; %s</b><br />\n", $k3);
                  
                  foreach($s3 as $k4 => $s4)
                  {
                    if(is_object($s4) || is_array($s4))
                    {
                      $out .= sprintf("<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&#x2514; %s:</b> <code style=\"color:grey\">%s</code><br />\n", $k4, is_array($s4) ? stripslashes(json_encode($s4)) : "instance of ".get_class($s4));
                      continue;
                    }
                    else
                    {
                      $out .= sprintf("<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&#x2514; %s:</b> <code style=\"color:grey\">%s</code><br />\n", $k4, $s4 ? htmlentities($s4) : '<em>NULL</em>');
                      continue;
                    }
                  }
                }
                elseif(is_object($s3) && !method_exists($s3, '__toString'))
                {  
                  $out .= sprintf("<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&#x2514; %s:</b><br />\n", $k4);
                  $out .= sprintf("<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&#x2514; %s:</b> <code style=\"color:grey\">%s</code><br />\n", $k3, "instance of class ".get_class($s3));
                  continue;
                }
                else
                {
                  $out .= sprintf("<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&#x2514; %s:</b> <code style=\"color:grey\">%s</code><br />\n", $k3, $s3 ? htmlentities($s3) : '<em>NULL</em>');
                  continue;
                }
              }
            }
            else
            {
              $out .= sprintf("<b>&nbsp;&nbsp;&#x2514; %s:</b> <code style=\"color:grey\">%s</code><br />\n", $k2, $s2 ? htmlentities($s2) : '<em>NULL</em>');
              continue;
            }
          }
        }
        elseif(is_scalar($setting))
        {
          $out .= sprintf("<b>%s:</b> <code style=\"color:grey\">%s</code><br />\n", $key, htmlentities($setting));
          continue;
        }
      }
    }
    $out .= "</div>";
    return $out;
  }
}