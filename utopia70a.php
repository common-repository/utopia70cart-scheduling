<?php
if (!function_exists('get_option')){exit();}
//------------------------------------------------------------------------------
/*
 * utopia70 plugin - admin side
 */
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
function utopia70_menuA()
{
  global $utopia70_dow,$utopia70_version,$wpdb;
  $maxTimeStamp='280101'; // latest override time that can be accepted
  $ts=(int)current_time( 'timestamp', 0 );
  $msg='';
  $id=( isset($_POST['u70_id']) ? (int)$_POST['u70_id'] : 0 );
  $tbsite = $wpdb->prefix . UTOPIA70_DBNAME_SITES;
  $tbslot = $wpdb->prefix . UTOPIA70_DBNAME_SLOTS;
  // clean up old db recs
  $k=date('ymd',$ts-3*86400);
  $sql="DELETE FROM $tbslot WHERE slot<='$k'";
  $wpdb->query( $sql );
  //
  if (isset($_POST['delete']))
  {
    $sql="DELETE FROM $tbsite WHERE ID=$id";
    $wpdb->query( $sql );
    $sql="DELETE FROM $tbslot WHERE siteid=$id";
    $wpdb->query( $sql );
    $msg="Location # $id deleted";
  }
  else if (isset($_POST['save']))
  {
    $fName=( isset($_POST['u70_name']) ? stripslashes(trim($_POST['u70_name'])) : '' );
    $fName=utopia70_strLimit($fName,95);
    $fDesc=( isset($_POST['u70_desc']) ? stripslashes(trim($_POST['u70_desc'])) : '' );
    $fDesc=utopia70_strLimit($fDesc,240);
    $fLook=( isset($_POST['u70_look']) ? intval($_POST['u70_look']) : 0 );
    $fLook=max(3,min($fLook,120)); // sanity check limits
    $fHourList='';// checkboxes
    for ($i=0;$i<168;++$i)
    {
      $fHourList=$fHourList.( isset($_POST["u70_c$i"]) ? 'A' : 'L' );
    }
    if ($id<=0) // add?
    {
      // get avail id - lowest that is > 0
      $sql="SELECT id FROM $tbsite ORDER BY id";
      $r = $wpdb->get_results( $sql );
      $id=1;
      foreach ($r as $k)
      {
        if ($id!=(int)($k->id))
        {
          break;
        }
        ++$id;
      }
      $sql=$wpdb->prepare("INSERT INTO $tbsite (id,name,info,look,dowf) VALUES ($id,%s,%s,$fLook,'$fHourList');",array($fName,$fDesc) );
      $wpdb->query( $sql );
      $msg="Location # $id created";
    }
    else
    {
      $sql=$wpdb->prepare("UPDATE $tbsite SET name=%s, info=%s, look=$fLook, dowf='$fHourList' WHERE id=$id; ",array($fName,$fDesc) );
      $wpdb->query( $sql );
      $msg="Location # $id updated";
    }
    // now filter/adjust datetimes list
    if (isset($_POST['u70_dl']) )
    {
      $k=' '.stripslashes(trim($_POST['u70_dl'])).' ';
      $i=intval(preg_match_all('#([\d]{2,4})[/\.-]([\d]{1,2})[/\.-]([\d]{1,2})([\+\-])([\d]{1,2})[^\d]#',$k,$m));
      // make up list of slots and if avail or not
      $a=array();
      $r=array();
      while (--$i>=0)
      {
        $t=mktime($m[5][$i],0,0,$m[2][$i],$m[3][$i],$m[1][$i],-1);
        if ($t>0)
        {
          $k=date("ymdG",$t);
          $a[$k]=$m[4][$i];
          $r[substr($k,0,6)]=1;
        }
      }
      // for all curr rec make new over fields and see if any changes needed
      $tA=date('ymd',$ts);
      $sql="SELECT slot,over FROM $tbslot WHERE siteid=$id AND slot>='$tA' AND slot<='$maxTimeStamp' ORDER BY slot";
      $rSlot=$wpdb->get_results( $sql );
      foreach ($rSlot as $k)
      {
        $r[$k->slot]=0;
        $c=0;
        $s=$k->slot;
        $o='************************';
        for ($i=0;$i<24;++$i)
        {
          $d=$s.$i;
          if ( isset($a[$d]) && '*'!=$a[$d] )
          {
            $o[$i]=$a[$d];
            $a[$d]='*';
          }
        }
        if ($o!=$k->over) // changed? write rec
        {
          $sql="UPDATE $tbslot SET over='$o' WHERE slot='$s' AND siteid=$id";
          $wpdb->query($sql);
        }
      }
      // now check if any remaining days to add as new entries to
      foreach ($r as $k=>$v)
      {
        if ($v>0)
        {
          $o='************************';
          for ($i=0;$i<24;++$i)
          {
            $d=$k.$i;
            if ( isset($a[$d]) && '*'!=$a[$d] )
            {
              $o[$i]=$a[$d];
            }
          }
          $sql="INSERT INTO $tbslot (siteid,slot,over,users) VALUES ($id,'$k','$o','|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|')";
          $wpdb->query($sql);
        }
      }
    }
  }
  if (''!=$msg)
  {
    $msg='<div class="u70mb">'.esc_attr($msg).'</div>';
  }
echo <<<HTML_CODE
<div class="wrap"><h2>J.W. Cart Scheduler - Edit Locations</h2>
$msg
You can set up Cart locations below by entering new ones in the last entry below
('Add New Site') and clicking on 'Save Changes', or delete locations with the
'Delete Location' button.

For each location, you can enter <b>Title</b>, <b>Description</b>, <b>Lookahead</b>
(the maximum number of days reservations can be made from today), as well as
<b>Available Times</b> (each check allows a reservation for that hour during the
week), and <b>Time Overrides</b>.
<br /><br />
Overrides are used to change the normal weekly schedule for specific dates.
They are entered <code>YYYY/MM/DD<u>?</u>HH</code> format, one for each hour you want to override, and
use a <code>+</code> or <code>-</code> in place of the <code><u>?</u></code> to either add the schedule slot or remove it. For
example if a location is
normally available at 8am, but you wish to cancel it for the 1st of January 2019, you
would use a <code>-</code> and enter: <code>2019/01/01-08</code> On the other hand, if you want to make a
normally-locked time available for one day, you would use a <code>+</code>. For
example to make the cart available for extended hours at the 7pm, 8pm, and 9pm slots on December 31st, 2019 you would enter
<code>2019/12/31+19 2019/12/31+20 2019/12/31+21</code> (note the 24-hour timing) making sure there is a space between entries (and no spaces within each entry).
HTML_CODE;

  $sql="SELECT * FROM $tbsite ORDER BY id";
  $rSite = $wpdb->get_results( $sql );
  $nTot=$wpdb->num_rows;
  $nTot=min($nTot,190); // clamp to reasonable limits
  $tA=date('ymd',$ts);
  for ($n=0;$n<=$nTot;++$n)
  {
    if ($n<$nTot)
    {
      $fID=$rSite[$n]->id;
      $fName=esc_attr($rSite[$n]->name);
      $fDesc=esc_attr($rSite[$n]->info);
      //$fDesc=htmlentities($rSite[$n]->info,ENT_QUOTES);
      $fLook=$rSite[$n]->look;
      $fHourList=$rSite[$n]->dowf;
      // get override list
      $sql="SELECT slot,over FROM $tbslot WHERE siteid=$fID AND slot>='$tA' AND slot<='$maxTimeStamp' ORDER BY slot";
      $rSlot=$wpdb->get_results( $sql );
      $fDateList='';
      foreach ($rSlot as $k)
      {
        for ($i=0;$i<24;++$i)
        {
          // over(ride) field is + for allow at time, - for lock at time, ? for don't care
          if ('-'==$k->over[$i]||'+'==$k->over[$i])
          {
            $d=$k->slot;
            $fDateList=$fDateList.'20'.$d[0].$d[1].'/'.$d[2].$d[3].'/'.$d[4].$d[5].$k->over[$i].substr('0'.$i,-2)." \n";
          }
        }
      }
      $ti="Location # $fID";
      $btnD=true;
      $btnS='Save Changes';
    }
    else // 'add location' entry
    {
      $fID=-1;
      $fName='';
      $fDesc='';
      $fLook=21;
      $fDateList='';
      $fHourList="LLLLLLLAAAAAAAAAAAAAALLLLLLLLLLAAAAAAAAAAAAAALLLLLLLLLLAAAAAAAAAAAAAALLLLLLLLLLAAAAAAAAAAAAAALLLLLLLLLLAAAAAAAAAAAAAALLLLLLLLLLAAAAAAAAAAAAAALLLLLLLLLLAAAAAAAAAAAAAALLL";
      $ti="Add New Site";
      $btnD=false;
      $btnS='Add New Site';
    }


echo <<<HTML_CODE
<br><br>
<form method='post'><INPUT type='hidden' name='u70_id' value='$fID'>
<table align='center' cellpadding='5' cellspacing='0' style='border:1px solid #83B4D8;'><tbody>
<tr><td colspan='3' bgcolor='#E5F3FF' align='center' ><b style='font-size:180%;'>$ti</b></td></tr>
<tr>
 <td align='right'>
  Title:
 </td>
 <td colspan='2'>
   <INPUT name='u70_name' value='$fName' size='65' maxlength="70">
 </td>
</tr>
<tr>
 <td align='right'>
  Desc:
 </td>
  <td colspan='2'>
   <textarea rows='2' cols='63' name='u70_desc' maxlength="200">$fDesc</TEXTAREA>
  </td>
</tr>
<tr>
 <td align='right'>
  Lookahead:
 </td>
  <td colspan='2'>
   <INPUT name='u70_look' value='$fLook' size='5' maxlength="6">&nbsp;Days&nbsp;Maximum
  </td>
</tr>

<tr>
 <td colspan='3' align='center' >

<table align='center' cellpadding='2' cellspacing='0' style='border:1px solid #83B4D8;'><tbody>
 <tr>
   <td align='center' valign='top'>
Available Times (Weekly): <br />
     <table align='center' cellpadding='2' cellspacing='0' style='border:1px solid #83B4D8;'><tbody>
HTML_CODE;

    echo '<tr>';
    for ($i=0;$i<2;++$i)
    {
      echo '<td>&nbsp;</td>';
      for ($k=0;$k<7;++$k)
      {
        echo '<td>'.$utopia70_dow[$k][0].'</td>';
      }
    }
    echo '</tr>';
    for ($k=0;$k<12;++$k)
    {
      echo '<tr>';
      for ($i=0;$i<2;++$i)
      {
        $d=(0==$k?12:$k).(0==$i?'am':'pm');
        echo "<td>$d</td>";
        for ($c=0;$c<7;++$c)
        {
          $h=$c*24+$k+$i*12;
          $ch=('A'==$fHourList[$h]?'checked':'');
          echo "<td><input type='checkbox' name='u70_c$h' $ch></td>";
        }
      }
      echo '</tr>';
    }

echo <<<HTML_CODE
     </tbody></table>
    </td>
    <td align='center' valign='top'>
     Time Overrides: <br />
     <TEXTAREA rows='14' cols='16' name='u70_dl'>$fDateList</TEXTAREA>
    </td>
   </tr>
  </tbody></table>

 </td>
</tr>
<tr>
  <td align='left'>
HTML_CODE;
  echo ( $btnD ? "<INPUT type='submit' name='delete' value='Delete Location' onclick=\"return confirm('Are you sure you want to delete this location (THIS IS PERMANENT)?')\">" : "&nbsp;" );
echo <<<HTML_CODE
  </td>
  <td align='center'>    &nbsp; </td>
  <td align='right'><INPUT type='submit' name='save' value='$btnS'></td>
</tr>
</tbody></table>
</form>
HTML_CODE;
  }
echo <<<HTML_CODE
<br /><br /><small><a href='http://www.utopiamechanicus.com/wp-plugin-j-w-cart-scheduler/'>J.W. Cart Scheduler WordPress Plugin v$utopia70_version</a> </small> <br /></div>
HTML_CODE;
}
//------------------------------------------------------------------------------
function utopia70_menuB()
{
  global $utopia70_version,$wpdb;
echo <<<HTML_CODE
<div class="wrap"><h2>J.W. Cart Scheduler - More Info</h2>

This plugin allows pages and posts to display special cart information using the following shortcodes:
  <br/><br/>
<code>[u70block loggedin='1']...[/u70block]</code> Anything between the two blocks is displayed, depending on whether the visitor is logged in or not:
  <br/>
<ul style='padding:0 0 0 2em;'>
<li><code>loggedin='1'</code> Displays only if visitor is logged in at Subscriber level or higher.</li>
<li><code>loggedin='0'</code> Displays only if visitor is NOT logged in.</li>
<li><code>loggedin='-1'</code> Never displays (except in the Admin editor). Useful for internal comments.</li>
</ul>

<code>[u70disp...</code> Displays various data, visible only if logged in at Subscriber level or higher:
  <br/>
<ul style='padding:0 0 0 2em;'>
<li><code>[u70disp type='title' location='1']</code> Shows the specified location's 'Title:' entry, as set in the Admin Panel.</li>
<li><code>[u70disp type='description' location='1']</code> Shows the location's 'Desc:' entry, as set in the Admin Panel.</li>
<li><code>[u70disp type='lookahead' location='1']</code> Shows the location's 'Lookahead:' value (max days to view/edit), as set in the Admin Panel.</li>
<li><code>[u70disp type='loginform']</code> The reverse of these other codes, this displays a login form if the visitor is NOT logged in, and nothing if they are.</li>
<li><code>[u70disp type='reservations' dayoffset='0' daylength='1']</code> Displays a reservation summary for the logged-in visitor, showing
  all time slots reserved in the day range. Both 'dayoffset' and 'daylength' are optional (defaults shown here) - 'dayoffset' is when to start
  the list, offset by the # of days from today, and 'daylength' is how many days to show.</li>
</ul>
<code>[u70cal location='1' days='12']</code> Displays a schedule calendar to logged-in viewers (level of Subscriber or higher.) 'location' and 'days' are optional (defaults shown):
  'location' is the location number to display, and 'days' is # days ahead that calendar shows (with the maximum the Admin 'Lookahead' value set for the site.)
  The display is a weekly calendar visitors can select from, and a day calendar below for reservations. Visitors click on a day to display that schedule below, and
  either check a slot to reserve it, or check to cancel a previously-reserved slot. They can also enter notes for each slot, and by pressing either 'Save' button, save the changes.
  Note that everyone can add, cancel, or edit their own reservations, but to edit or cancel another's reservation, they require a level of Contributor or higher.
<br/><br/>
  For coloring and styling of the display elements consult this plugins's style.css file.
<br/><br/>
  For further info visit <a href='http://www.utopiamechanicus.com/wp-plugin-j-w-cart-scheduler/'>http://www.utopiamechanicus.com/wp-plugin-j-w-cart-scheduler/</a>

<h3>Slots DB Rec Dump</h3>
  The slot records from the DB to help with debugging:
 <pre>
HTML_CODE;
  $tbslot = $wpdb->prefix . UTOPIA70_DBNAME_SLOTS;
  $sql="SELECT * FROM $tbslot ORDER BY siteid,slot";
  $rSlot=$wpdb->get_results( $sql );
  $i=-1;
  foreach ($rSlot as $k)
  {
    if ($i!=$k->siteid)
    {
      $i=$k->siteid;
      echo "<br/>Location #$i:<br/>";
    }
    echo "$k->slot $k->over $k->users<br/>";
    echo '   NOTES=['.esc_attr($k->notes).']<br/>';
  }
echo <<<HTML_CODE
  </pre>
<br /><small><a href='http://www.utopiamechanicus.com/wp-plugin-j-w-cart-scheduler/'>J.W. Cart Scheduler WordPress Plugin v$utopia70_version</a> </small> <br /></div>
HTML_CODE;
}
//------------------------------------------------------------------------------
function utopia70_update_database_table() {
  global $wpdb;
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  $charset_collate = $wpdb->get_charset_collate();
  $table = $wpdb->prefix . UTOPIA70_DBNAME_SITES;
  $sql = "CREATE TABLE $table (
    id SMALLINT NOT NULL,
    dowf CHAR(168) DEFAULT '' NOT NULL,
    name CHAR(100) DEFAULT '' NOT NULL,
    info CHAR(250) DEFAULT '' NOT NULL,
    look SMALLINT DEFAULT 0 NOT NULL,
    UNIQUE KEY id (id)
  ) $charset_collate;";
  dbDelta($sql);
  $table = $wpdb->prefix . UTOPIA70_DBNAME_SLOTS;
  $sql = "CREATE TABLE " . $table . " (
    slot CHAR(6) NOT NULL,
    siteid SMALLINT NOT NULL,
    over CHAR(24) DEFAULT '' NOT NULL,
    users VARCHAR(245) DEFAULT '' NOT NULL,
    notes VARCHAR(750) DEFAULT '' NOT NULL,
    UNIQUE KEY slotsiteid (slot,siteid)
    );";
  // users size: max 9-digit user id X 24 hrs + 25 sep char = 241
  // notes size: max 30-char note X 24 hrs + 25 sep char = 745
  dbDelta($sql);
}
//------------------------------------------------------------------------------
function utopia70_menuAdd() {
  global $utopia70_file;
  // https://developer.wordpress.org/reference/functions/add_submenu_page/
  add_menu_page("J.W. Cart Scheduler", 'J.W.Cart Scheduler', 'administrator', 'utopia70', 'utopia70_menuA','dashicons-calendar-alt');//$i);
  add_submenu_page('utopia70', 'Edit Locations', 'Edit Locations', 'administrator','utopia70','utopia70_menuA');
  add_submenu_page('utopia70', 'More Info', 'More Info', 'administrator','utopia70b','utopia70_menuB');
}
add_action('admin_menu', 'utopia70_menuAdd');
