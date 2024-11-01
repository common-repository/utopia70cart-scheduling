<?php
if (!function_exists('get_option')){exit();}
/*==============================================================================
Plugin Name: J.W. Cart Schedule (utopia70) 
Version: 0.91
Plugin URI: http://www.utopiamechanicus.com/wp-plugin-j-w-cart-scheduler/
Description: Plugin to handle J.W.Cart assignments for multiple locations via shortcodes
Author: utopiamech (D.Pankhurst)
Author URI: http://www.utopiamechanicus.com/
License: GPL version 2 (http://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
Text domain: utopia70cart-scheduling

v0.91
first release

==============================================================================*/
$utopia70_version='0.91';
define('UTOPIA70_VERSION_KEY','UTOPIA70_VERSION_KEY');
define('UTOPIA70_DBNAME_SITES','u70site');
define('UTOPIA70_DBNAME_SLOTS','u70slot');
$utopia70_dow=array('Sun','Mon','Tue','Wed','Thu','Fri','Sat');
$utopia70_options=array();
$utopia70_currPage='';
$utopia70_siteData=array();
$utopia70_loggedIn=false;
$utopia70_canDelete=false;
$utopia70_userID=0;
$utopia70_userName='';
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
function utopia70_init()
{
  global $utopia70_loggedIn, $utopia70_canDelete, $utopia70_userID,$utopia70_postR,$utopia70_postC,$utopia70_currPage,$utopia70_dow,$utopia70_userName,$utopia70_options,$utopia70_version,$wpdb;
  $utopia70_options=get_option(UTOPIA70_VERSION_KEY);
  settype($utopia70_options,'array');
  if ($utopia70_options['v'] != $utopia70_version) {
    require_once(dirname(__FILE__).'/utopia70a.php');
    utopia70_update_database_table();
    $utopia70_options['v']=$utopia70_version;
    update_option(UTOPIA70_VERSION_KEY,$utopia70_options);
  }
  $currUser = wp_get_current_user(); // https://codex.wordpress.org/wp_get_current_user
  if ( $currUser->exists() && $currUser->has_cap('read') )
  {
    $utopia70_loggedIn=true;
    $utopia70_userName=$currUser->display_name;
    // subscribers cannot edit, but contributors on up can - se https://codex.wordpress.org/Roles_and_Capabilities
    $utopia70_canDelete=$currUser->has_cap('edit_posts');
    $utopia70_userID=$currUser->ID;
    // check if get var or post vars
    if ( isset($_REQUEST['u70d']) )
    {
      $v=$_REQUEST['u70d'];
      if (preg_match('/^(\d\d)(\d\d)(\d\d)$/', $v, $m))
      {
        if ( '18'<=$m[1]&&$m[1]<='30' && '01'<=$m[2]&&$m[2]<='12' && '01'<=$m[3]&&$m[3]<='31' )
        {
          $utopia70_currPage=$v;
        }
      }
    }
    if (0==strlen($utopia70_currPage))
    {
      $utopia70_currPage=date('ymd',(int)current_time( 'timestamp', 0 ));
    }
    //
    // update data if logged in and any present
    $site=( isset($_POST['u70l']) ? intval($_POST['u70l']) : -1 );
    $i=0;
    if ( $site>0 && $utopia70_loggedIn && strlen($utopia70_currPage)>0 && isset($_POST['u70user']) && $utopia70_userID==intval($_POST['u70user']) )
    {
      $aP=array_fill(0,24,-1);
      $aC=array_fill(0,24,'');
      foreach ($_POST as $k=>$v)
      {
        if ( 'u70_'==substr($k,0,4) )
        {
          if (preg_match('/^u70_([rct])(\d{1,2})$/', $k, $m))
          {
            $h=intval($m[2]);
            if ( 0<=$h && $h<=23 )
            {
              $i=1;
              if ('t'==$m[1])
              {
                $aC[$h]=utopia70_strLimit(str_replace('|','I',stripslashes(trim($_POST[$k]))),25);
              }
              else
              {
                $aP[$h]=( 'c'==$m[1] ? 0 : 1 );
              }
            }
          }
        }
      }
      if (0!=$i)
      {
        $tbslot = $wpdb->prefix . UTOPIA70_DBNAME_SLOTS;
        $sql="SELECT users,notes FROM $tbslot WHERE siteid=$site AND slot='$utopia70_currPage'";
        $rSlot = $wpdb->get_row( $sql );
        $aD=array_fill(0,24,0);
        $aN=array_fill(0,24,'');
        if (isset($rSlot->users))
        {
          $a=explode('|',substr($rSlot->users,1));
          $aD=array_slice(array_merge($a,$aD),0,24);
          $a=explode('|',substr($rSlot->notes,1));
          $aN=array_slice(array_merge($a,$aN),0,24);
        }
        for ($i=0;$i<24;++$i)
        {
          if ( 0==$aP[$i] && ( $utopia70_userID==$aD[$i] || $utopia70_canDelete ) ) // can only clear if your own or permitted to del
          {
            $aD[$i]=0;
            $aN[$i]='';
          }
          else if ( 1==$aP[$i] && $aD[$i]<=0 ) // can add if slot empty
          {
            $aD[$i]=$utopia70_userID;
            $aN[$i]=$aC[$i];
          }
          else if (0!=strcmp($aN[$i],$aC[$i])) // entered/changed comment w/o checkbox set?
          {
            if ( $aD[$i]<=0 ) // empty slot? ok to add it
            {
              $aD[$i]=$utopia70_userID;
              $aN[$i]=$aC[$i];
            }
            else if ( $utopia70_userID==$aD[$i] || $utopia70_canDelete ) // if we're allowed to delete, we're allowed to edit - go ahead
            {
              $aN[$i]=$aC[$i];
            }
          }
        }
        $k='|'.implode('|',$aD).'|';
        $m='|'.implode('|',$aN).'|';
        if (isset($rSlot->users))
        {
          $sql=$wpdb->prepare("UPDATE $tbslot SET users='$k',notes='%s' WHERE  siteid=$site AND slot='$utopia70_currPage'",array($m) );
        }
        else
        {
          $sql=$wpdb->prepare("INSERT INTO $tbslot (siteid,slot,users,notes,over) VALUES ($site,'$utopia70_currPage','$k','%s','************************'); ",array($m));
        }
        $wpdb->query( $sql );
      }
    }
  }
  // add js and css code - https://stackoverflow.com/questions/3760222/how-to-include-css-and-jquery-in-my-wordpress-plugin
  wp_register_style('utopia70', plugins_url('style.css',__FILE__ ));
  wp_enqueue_style('utopia70');
  wp_register_script( 'utopia70', plugins_url('script.js',__FILE__ ));
  wp_enqueue_script('utopia70');
}
//------------------------------------------------------------------------------
function utopia70_shortcodeBlock( $atts, $content = null )
{
  // loggedin can be <0 (never show text) 0 (show if NOT logged in) >0 (show if logged in)
  global $utopia70_loggedIn, $utopia70_canDelete;
  $a = shortcode_atts( array(
        'loggedin' => '-1'
    ), $atts );
  $i=(int)$a['loggedin'];
  if ( $i<0 || ( $i==0 && $utopia70_loggedIn ) || ( $i>0 && !$utopia70_loggedIn ) )
  {
    return '';
  }
  return do_shortcode($content);
}
//------------------------------------------------------------------------------
function utopia70_shortcodeDisplay( $atts )
{
  global $utopia70_loggedIn, $utopia70_canDelete, $utopia70_siteData, $utopia70_userName, $utopia70_userID, $wpdb;
  $a = shortcode_atts( array(
        'type' => 'loginform',
        'location' => '-1',
        'dayoffset' => '0', // only with 'reservations'
        'daylength' => '1' // only with 'reservations'
    ), $atts );
  $t=strtolower($a['type']);
  $tbsite = $wpdb->prefix . UTOPIA70_DBNAME_SITES;
  $tbslot = $wpdb->prefix . UTOPIA70_DBNAME_SLOTS;
  switch ($t)
  {
    case 'loginform':
      // https://developer.wordpress.org/reference/functions/wp_login_form/
      if (!$utopia70_loggedIn)
        return '<div class="u70login">'.wp_login_form('echo=0').'</div>';
      break;
    case 'username':
      if ($utopia70_loggedIn)
        return $utopia70_userName;
      break;
    case 'reservations':
      if (!$utopia70_loggedIn)
      {
        return '';
      }
      $da=intval($a['dayoffset']);
      $dl=intval($a['daylength']);
      $da=max(0,min($da,99));
      $dl=max(1,min($dl,31));
      $ts=intval(current_time( 'timestamp', 0 ))+86400*$da;
      $sA=date('ymd',$ts);
      $sB=date('ymd',$ts+86400*$dl);
      $sql="SELECT name,slot,siteid,users FROM $tbslot INNER JOIN $tbsite ON siteid=id WHERE slot>='$sA' AND slot<'$sB' AND users LIKE '%|$utopia70_userID|%' ORDER BY slot,siteid";
      $rSlot = $wpdb->get_results( $sql );
      $tot=$wpdb->num_rows;
      $t='';
      $r=0;
      $ot=false;
      for ($k=0;$k<$dl;++$k)
      {
        $dp=$s=date('D M j',$ts );
        $t=$t."<div class='u70ssd'>Your Reservations for $dp:";
        if ( $r>=$tot || $rSlot[$r]->slot>$sA )
        {
          $t=$t."<div class='u70sse'>No Reservations for this date</div>";
        }
        else
        {
          while ( $r<$tot && $rSlot[$r]->slot==$sA ) // sync to curr rec
          {
            $dp=$s=date('D M j',$ts );
            $aUsers=explode('|',substr($rSlot[$r]->users,1));
            $t=$t."<div class='u70ssh'>Location #".$rSlot[$r]->siteid.": ".esc_attr($rSlot[$r]->name)."</div>";
            $pHr=-1;
            $i=0;
            while (++$i<24)
            {
              if ($utopia70_userID==intval($aUsers[$i]))
              {
                $pHr=$i;
                while ( ++$i<24 && $utopia70_userID==intval($aUsers[$i]) )
                  {;}
                $s='<div class="u70ssi">'.utopia70_getHourString('##t:00&nbsp;##p - ',$pHr).utopia70_getHourString('##t:00&nbsp;##p',$i ).':  '.($i-$pHr).' hour(s)</div>';
                $t=$t.$s;
              }
            }
            ++$r;
          }
        }
        $t=$t.'</div>';
        $ts=$ts+86400;
        $sA=date('ymd',$ts);
      }
      return $t;
    default:
      // assume rest are entries for the current site - get them
      $i=(int)$a['location'];
      if ($i>0&&$utopia70_loggedIn)
      {
        if (!isset($utopia70_siteData[$i]))
        {
          $sql="SELECT * FROM $tbsite WHERE id=$i LIMIT 1";
          $utopia70_siteData[$i] = $wpdb->get_row( $sql );
          if (!isset($utopia70_siteData[$i]))
          {
            return '';
          }
        }
        switch ($t)
        {
          case 'title':
            return esc_attr($utopia70_siteData[$i]->name);
          case 'description':
            return esc_attr($utopia70_siteData[$i]->info);
          case 'lookahead':
            return $utopia70_siteData[$i]->look;
        }
      }
  }
  return '';
}
//------------------------------------------------------------------------------
function utopia70_shortcodeCalendar( $atts )
{
  global $utopia70_loggedIn,$utopia70_canDelete,$utopia70_userID,$utopia70_postC,$utopia70_postR,$utopia70_currPage,$utopia70_dow,$wpdb,$wp;
  if (!$utopia70_loggedIn)
    return '';
  // from here on, only users logged in can see - process any form data if same user
  $a = shortcode_atts( array(
        'location' => '1',
        'days' => '12'
    ), $atts );
  //$current_url = home_url( add_query_arg( array(), $wp->request ) );
  $i = get_queried_object_id();
  $current_url = ( $i>0 ? get_permalink( $i ) : '' );
  $pr=( FALSE===strpos($current_url,'?') ? '?' : '&' );
  //
  $site=intval($a['location']);
  if ($site<=0)
  {
    $site=1;
  }
  $tbsite = $wpdb->prefix . UTOPIA70_DBNAME_SITES;
  $tbslot = $wpdb->prefix . UTOPIA70_DBNAME_SLOTS;
  $sql="SELECT * FROM $tbsite WHERE id=$site LIMIT 1";
  $rSite = $wpdb->get_row( $sql );
  if (!isset($rSite))
    return "Location $site not set up yet!";
  //
  $c=intval($a['days']);
  if ($c<3)
    $c=3;
  else if($c>$rSite->look)
    $c=$rSite->look;
  // get timestamps for each day displayed
  $ts=intval(current_time( 'timestamp', 0 ));
  // shift to noon to avoid any dst or boundary issues
  $ts=mktime(12,0,0,date('n',$ts),date('j',$ts),date('Y',$ts));//
  $dispDay=0;
  for ($k=0;$k<$c;++$k)
  {
    $at[$k]=$ts;
    $ad[$k]=date('ymd',$ts); // eg: 190131
    if ($utopia70_currPage==$ad[$k])
    {
      $dispDay=$k;
    }
    $ts+=86400;
  }
  $dispDayDOW=date('w',$at[$dispDay]);
  $dowMask=substr($rSite->dowf,24*$dispDayDOW,24);
  // now get curr day info, and users for current day
  $sql="SELECT slot,over,users,notes FROM $tbslot WHERE siteid=$site AND slot='$ad[$dispDay]'";
  $rSlot = $wpdb->get_row( $sql );
  $aNotes=array_fill(0,24,'');
  if(isset($rSlot))
  {
    $aOver=$rSlot->over;
    $aUsers=explode('|',substr($rSlot->users,1)); // skip first '|' so we are synced
    $a=explode('|',substr($rSlot->notes,1));
    $aNotes=array_slice(array_merge($a,$aNotes),0,24);
  }
  else
  {
    $aOver='************************';
    $aUsers=array_fill(0,24,0);
  }
  // get users display names
  $aUID=array($utopia70_userID=>'');
  for ($k=0;$k<24;++$k)
  {
    if ( $aUsers[$k]>0 )
    {
      $aUID[$aUsers[$k]]='';
    }
  }
  // info: https://codex.wordpress.org/Function_Reference/get_userdata
  // get names - all ids in $an
  $n=implode(",", array_keys($aUID));
  $sql="SELECT ID,display_name FROM $wpdb->users WHERE ID IN ($n)";
  $rID = $wpdb->get_results( $sql );
  $aUID=array(); // wipe & reload so we avoid invalid members listed
  foreach ($rID as $r)
  {
    $aUID[$r->ID]=$r->display_name;
  }
  //
  // display calendar
  //
  $t='';
  $dow=date('w',$at[0]);
  $n=$dow+$c+6;
  $n=((int)($n/7))*7;
  $s='<div class="u70c"><table cellspacing="0" cellpadding="2"><tbody><tr>';
  for ($i=0;$i<7;++$i)
  {
    $s=$s."<td width='14%' class='u70ch'>$utopia70_dow[$i]</td>";
  }
  $s=$s.'</tr>';
  $k=0;
  $ts=$at[0]-$dow*86400;
  for ($i=0;$i<$n;++$i)
  {
    if (0==$k++)
    {
      $s=$s.'<tr>';
    }
    $v=date('M',$ts).'<br />'.date('j',$ts);
    $ts=$ts+86400;
    if ( $i<$dow || $dow+$c-1<$i )
    {
      $s=$s.'<td class="u70cu">'.$v.'</td>';
    }
    else
    {
      $u=$current_url.$pr.'u70d='.$ad[$i-$dow];
      if ($i==$dispDay+$dow)
      {
        $s=$s."<td class='u70cc'><a href='$u'>$v</a></td>";
      }
      else if ($i==$dow)
      {
        $s=$s."<td class='u70ct'><a href='$u'>$v</a></td>";
      }
      else
      {
        $s=$s."<td class='u70co'><a href='$u'>$v</a></td>";
      }
    }
    if (7==$k)
    {
      $k=0;
      $s=$s.'</tr>';
    }
  }
  $t=$t.$s.'</tr></tbody></table></div>';
  //
  // display single day
  //
$s= <<<DATA_CODE
<br/>
<div class='u70d'>
  <form action='$current_url' method='post'>
    <input type='hidden' name='u70l' value='$site'><input type='hidden' name='u70d' value='$utopia70_currPage'>
    <input type='hidden' name='u70user' value='$utopia70_userID'>
DATA_CODE;
  $t=$t.$s;
  //
  $e=array();
  for ($hr=0;$hr<24;++$hr)
  {
    $v=$ad[$dispDay];
    // figure out if hr is available or locked or avail or in use - and if so add user id to display
    $u='';
    $a1=$aOver[$hr];
    $a2=$dowMask[$hr];
    if ( '-'==$aOver[$hr] || ( '+'!=$aOver[$hr] && 'L'==$dowMask[$hr] ) ) // locked if override enabled, or dow locked (and no override for it)
    {
      $st=-1;
    }
    else if ($aUsers[$hr]>0) // user in slot?
    {
      $st=1;
      if ( isset($aUID[$aUsers[$hr]]) ) // slot id exists?
      {
        $u=esc_attr($aUID[$aUsers[$hr]]);
      }
      else // invalid user (deleted?)
      {
        $u='?????'; // leave it in for now - rarely happens
      }
    }
    else
    {
      $st=0;

    }
    $tx=esc_attr($aNotes[$hr]);
    $k=utopia70_getHourString('##t:00-##t:59##p',$hr );
    $tn=utopia70_getHourString('##t##p',$hr );
    switch ($st)
    {
      case 0:
        $e[$hr]="<td class='u70du'><div class='u70dt'>$k <input type='checkbox' id='u70_r$hr' name='u70_r$hr' value='1' />&nbsp;Reserve?</div> AVAILABLE <br /><input type='text' class='u70di' id='u70_t$hr' name='u70_t$hr' value='$tx' maxlength='25' /> </td>";
        break;
      case 1:
        $u="<span class='u70u'>$u</span>";
        $cl=( $aUsers[$hr]==$utopia70_userID ? 'u70dc' : 'u70da' );
        if ( $utopia70_canDelete || ( $aUsers[$hr]>0 && $aUsers[$hr]==$utopia70_userID ) )
        {
          $e[$hr]="<td class='$cl'><div class='u70dt'>$k <input type='checkbox' id='u70_c$hr' name='u70_c$hr' value='1' onchange='utopia70_jsConfirm(\"u70_c$hr\",\"To prevent accidents, please click OK to confirm you want to Cancel @$tn\");' />&nbsp;CANCEL</div> $u <br /> <input type='text' class='u70di' id='u70_t$hr' name='u70_t$hr' value='$tx' maxlength='25' /> </td>";
        }
        else
        {
          $e[$hr]="<td class='$cl'><div class='u70dt'>$k</div> $u <div class='u70dn'>$tx</div> </td>";
        }
        break;
      default:
        $e[$hr]="<td class='u70dl'><div class='u70dt'>$k</div> NOT AVAILABLE</br>&nbsp; </td>";
        break;
    }
  }
  $u='';
  for ($i=0;$i<12;++$i)
  {
    $u=$u.'<tr>'.$e[$i].$e[$i+12].'</tr>';
  }
  $s=date('D M j',$at[$dispDay] );
$s= <<<DATA_CODE
<table cellspacing='0' cellpadding='2'>
  <tbody>
    <tr>
      <td align='center'><span class='u70dh'>$s</span></td>
      <td align='center'><input type='submit' value='Save'></td>
    </tr>
    <tr><td><span class='u70am'>A.M.</span></td><td><span class='u70am'>P.M.</span></td></tr>
    $u
    <tr>
      <td align='center' class='u70dh'><span class='u70i'>Location $site</span></td>
      <td align='center' class='u70dh'><input type='submit' value='Save' style='u70ib'></td>
    </tr>
  </tbody>
</table>
DATA_CODE;
  $t=$t.$s."</form></div>";
  return $t;
}
//------------------------------------------------------------------------------
function utopia70_strLimit( $s,$n )
{
  if (strlen($s)>$n)
  {
    return substr($s,0,$n);
  }
  return $s;
}
//------------------------------------------------------------------------------
function utopia70_getHourString( $d,$hr )
{
  if($hr>=13)
  {
    $k=$hr-12;
    $p='pm';
  }
  else if($hr>=12)
  {
    $k=$hr;
    $p='pm';
  }
  else if($hr>=1)
  {
    $k=$hr;
    $p='am';
  }
  else
  {
    $k=12;
    $p='am';
  }
  $k=str_replace(array('##t','##p'),array($k,$p),$d);
  return $k;
}
//------------------------------------------------------------------------------
if (is_admin())
{
  require_once(dirname(__FILE__).'/utopia70a.php');
}
//------------------------------------------------------------------------------
add_shortcode( 'u70block', 'utopia70_shortcodeBlock' );
add_shortcode( 'u70disp', 'utopia70_shortcodeDisplay' );
add_shortcode( 'u70cal', 'utopia70_shortcodeCalendar' );
add_action( 'init', 'utopia70_init' );
