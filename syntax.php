<?php
/**
 * DokuWiki Syntax Plugin WikiCalendar
 * 
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Michael Klier <chi@chimeric.de>
 */
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_wikicalendar extends DokuWiki_Syntax_Plugin {
 
    /**
     * namespace of the calendar
     */
    var $calendar_ns    = '';
 
    /**
     * namespace of the current viewed month
     */
    var $month_ns       = '';
 
    /**
     * indicator if first week of the month has been generated
     */
    var $firstWeek      = false;
 
    /**
     * array with localisations of weekdays
     */
    var $langDays       = array();
 
    /**
     * array with localistaions of month
     */
    var $langMonth      = array();
 
    /**
     * the current date
     */
    var $curDate        = array();
 
    /**
     * the month to show
     */
    var $showMonth      = '';
 
    /**
     * the year to show
     */
    var $showYear       = '';
 
    /**
     * the global timestamp for date-operations
     */
    var $gTimestamp     = '';
 
    /**
     * number days of the current month
     */
    var $numDays        = '';
 
    /**
     * date-date to generate the calendar 
     */
    var $viewDate       = array();
 
    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Michael Klier (chi)',
            'email'  => 'chi@chimeric.de',
            'date'   => @file_get_contents(DOKU_PLUGIN.'wikicalendar/VERSION'),
            'name'   => 'WikiCalendar Plugin',
            'desc'   => 'Implements a simple Calendar with links to wikipages.',
            'url'    => 'http://dokuwiki.org/plugin:wikicalendar'
        );
    }
 
    /**
     * Some Information first.
     */
    function getType() { return 'substition'; }
    function getPType() { return 'block'; }
    function getSort() { return 125; }
 
    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{{cal>.+?}}',$mode,'plugin_wikicalendar');
    }
 
    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        $match = substr($match,6,-2);
        return array($match);
    }
 
    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        global $ID;
        global $conf;

        if($mode == 'xhtml'){

            $tz = date_default_timezone_get();
            if($this->getConf('timezone')) {
                date_default_timezone_set($this->getConf('timezone'));
            }

            // define some variables first
            $this->calendar_ns  = ($data[0]) ? $data[0] : $ID;
            $this->langDays     = $this->getLang('days');
            $this->langMonth    = $this->getLang('month');
            $this->curDate      = getdate(time());
            $this->showMonth    = (is_numeric($_REQUEST['plugin_wikicalendar_month'])) ? $_REQUEST['plugin_wikicalendar_month'] : $this->curDate['mon'];
            $this->showYear     = (is_numeric($_REQUEST['plugin_wikicalendar_year']))  ? $_REQUEST['plugin_wikicalendar_year']  : $this->curDate['year'];
            $this->gTimestamp   = mktime(0,0,0,$this->showMonth,1,$this->showYear); 
            $this->numDays      = date('t',$this->gTimestamp);
            $this->viewDate     = getdate($this->gTimestamp);
            $this->today        = ($this->viewDate['mon'] == $this->curDate['mon'] && 
                                   $this->viewDate['year'] == $this->curDate['year']) ? 
                                   $this->curDate['mday'] : null;

            // if month directory exists we keep the old scheme
            if(is_dir($conf['datadir'].'/'.str_replace(':','/',$this->calendar_ns.':'.$this->showYear.':'.$this->showMonth))) {
                $this->month_ns = $this->calendar_ns.':'.$this->showYear.':'.$this->showMonth;
            } else {
                if($this->showMonth < 10) {
                    $this->month_ns = $this->calendar_ns.':'.$this->showYear.':0'.$this->showMonth;
                } else {
                    $this->month_ns = $this->calendar_ns.':'.$this->showYear.':'.$this->showMonth;
                }
            }

            if($this->MonthStart == 7 && $this->getConf('weekstart') == 'Sunday') {
                $this->MonthStart = 0;
            } else {
                $this->MonthStart = ($this->viewDate['wday'] == 0) ? 7 : $this->viewDate['wday'];
            }
 
            // turn off caching
            $renderer->info['cache'] = false;
 
            $renderer->doc .= '<div class="plugin_wikicalendar">' . DOKU_LF;
            $renderer->doc .= $this->_month_xhtml();
            $renderer->doc .= $this->_form_go2();
            $renderer->doc .= '</div>' . DOKU_LF;
 
            date_default_timezone_set($tz);
            return true;
        }

        return false;
    }
 
    /**
     * Renders the Calendar (month-view) 
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    function _month_xhtml() {
        global $ID;

        $script = script();
 
        $prevMonth  = ($this->showMonth-1 > 0)  ? ($this->showMonth-1) : 12;
        $nextMonth  = ($this->showMonth+1 < 13) ? ($this->showMonth+1) : 1;
 
        switch(true) {
            case($prevMonth == 12):
                $prevYear = ($this->showYear-1);
                $nextYear = $this->showYear;
                break;
            case($nextMonth == 1):
                $nextYear = ($this->showYear+1);
                $prevYear = $this->showYear;
                break;
            default:
                $prevYear = $this->showYear;
                $nextYear = $this->showYear;
                break;
        }
 
        // create calendar-header 
        $out .= <<<CALHEAD
<table class="plugin_wikicalendar">
  <tr>
    <th class="month">
      <form action="{$script}" method="post" class="prevnext">
        <input type="hidden" name="id" value="{$ID}" />
        <input type="hidden" name="plugin_wikicalendar_year" value="{$prevYear}" />
        <input type="hidden" name="plugin_wikicalendar_month" value="{$prevMonth}" />
        <input type="submit" class="btn_prev_month" name="submit" value="&larr;" accesskey="P" title="{$this->langMonth[$prevMonth]} {$prevYear}" />
      </form>
    </th>
    <th class="blank">&nbsp;</th>
    <th class="blank">&nbsp;</th>
    <th class="month">{$this->langMonth[$this->viewDate['mon']]}<br />{$this->showYear}<br /></th>
    <th class="blank">&nbsp;</th>
    <th class="blank">&nbsp;</th>
    <th class="month">
      <form action="{$script}" method="post" class="prevnext">
        <input type="hidden" name="id" value="{$ID}" />
        <input type="hidden" name="plugin_wikicalendar_year" value="{$nextYear}" />
        <input type="hidden" name="plugin_wikicalendar_month" value="{$nextMonth}" />
        <input type="submit" class="btn_next_month" name="submit" value="&rarr;" accesskey="N" title="{$this->langMonth[$nextMonth]} {$nextYear}" />
      </form>
    </th>
  </tr>
CALHEAD;
 
        // create calendar weekday-headers
        $out .= "<tr>";
        if($this->getConf('weekstart') == 'Sunday') {
            $last = array_pop($this->langDays);
            array_unshift($this->langDays, $last);
        }
        foreach($this->langDays as $day) {
            $out .= '<td class="weekday">'.$day.'</td>'; 
        }
        $out .= "</tr>\n";
 
        // create calendar-body
        for($i=1;$i<=$this->numDays;$i++) {
            $day = $i;
            //set day-wikipage - use leading zeros on new pages
            if($day < 10) {
                if(page_exists($this->month_ns.':'.$day)) {
                    $dayWP = $this->month_ns.':'.$day;
                } else {
                    $dayWP = $this->month_ns.':0'.$day;
                }
            } else {
                $dayWP = $this->month_ns.':'.$day;
            }
            // close row at end of week
            if($wd == 7) $out .= '</tr>';
            // set weekday
            if(!isset($wd) or $wd == 7) { $wd = 0; }
            // start new row when new week starts
            if($wd == 0) $out .= '<tr>';
 
            // create blank fields up to the first day of the month
            $offset = ($this->getConf('weekstart') == 'Sunday') ? 0 : 1;
            if(!$this->firstWeek) {
                while($wd < ($this->MonthStart - $offset)) {
                    $out .= '<td class="blank">&nbsp;</td>';
                    $wd++;
                }
                // ok - first week is printet
                $this->firstWeek = true;
            }
 
            // check for today
            if($this->today == $day) {
                $out .= '<td class="today">'.$this->_calendar_day($dayWP,$day).'</td>';
            } else {
                $out .= '<td class="day">'.$this->_calendar_day($dayWP,$day).'</td>';
            }
 
            // fill remaining days with blanks 
            if($i == $this->numDays && $wd < 7) {
                while($wd<7) {
                    $out .= '<td class="blank">&nbsp;</td>';
                    $wd++;
                }
                $out .= '</tr>';
            }
 
            // dont forget to count weekdays
            $wd++;
        }
 
        // finally close the table
        $out .= '</table>';
 
        return ($out);
    }
 
    /**
     * Generates the content of each day in the calendar-table.
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    function _calendar_day($wp, $day) {
        global $lang;
        global $ID;
 
        if(file_exists(wikiFN($wp))) {
            $out .= '<div class="isevent">';
            if(auth_quickaclcheck($wp) >= AUTH_READ) {
                $out .= '<a href="' . wl($wp, array('do' => 'edit', 'plugin_wikicalendar_redirect_id' => $ID, 'plugin_wikicalendar_month' => $this->showMonth, 'plugin_wikicalendar_year' => $this->showYear)) . '" class="plugin_wikicalendar_btn" title="' . $lang['btn_edit'] . '"><img src="' . DOKU_BASE . 'lib/images/edit.gif" alt="' . $lang['edit_btn'] . '"/></a>' . DOKU_LF;
            }
            $out .= '<div class="day_num"><a href="' . wl($wp) . '" class="wikilink1" title="' . $wp . '">'.$day.'</a></div>';
            $out .= '<div class="abstract">' . p_get_metadata($wp, 'description abstract') . '</div>' . DOKU_LF;
        } else {
            $out .= '<div class="noevent">';
            if(auth_quickaclcheck($wp) >= AUTH_CREATE) {
                //$out .= $this->_btn_add_day($wp);
                $out .= '<a href="' . wl($wp, array('do' => 'edit', 'plugin_wikicalendar_redirect_id' => $ID, 'plugin_wikicalendar_month' => $this->showMonth, 'plugin_wikicalendar_year' => $this->showYear)) . '" class="plugin_wikicalendar_btn" title="' . $lang['btn_create'] . '"><img src="' . DOKU_BASE . 'lib/images/edit.gif" alt="' . $lang['edit_btn'] . '" /></a>' . DOKU_LF;
            }
            $out .= '<div class="day_num">'.$day.'</div>';
        }
        $out .= '</div>';
        return ($out);
    }
 
    /**
     * Generates a From to jump to selected dates
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    function _form_go2() {
        global $ID;
 
        $out .= '<table class="inline plugin_wikicalendar_go2">' . DOKU_LF;
 
        $out .= '<form action="'.script().'" method="post">' . DOKU_LF;
        $out .= '<tr class="default">' . DOKU_LF;
        $out .= '<td><label>'.$this->getLang('year').':</label></td>' . DOKU_LF;
        $out .= '<td><div class="input">' . DOKU_LF;
        $out .= '<select id="year" name="plugin_wikicalendar_year">' . DOKU_LF;

        $year_start = ($this->showYear != $this->curDate['year']) ? $this->showYear - 10 : $this->curDate['year'] - 5;
        $year_end   = $this->showYear + 10;
 
        for($i=$year_start;$i<=$year_end;$i++) {
            if($i == $this->showYear || $i == $this->curDate['year']) {
                $out .= '<option value="'.$i.'" selected="selected">'.$i.'</option>' . DOKU_LF;
            } else {
                $out .= '<option value="'.$i.'">'.$i.'</option>' . DOKU_LF;
            }
        }
 
        $out .= '</select>' . DOKU_LF;
        $out .= '</div>' . DOKU_LF;
        $out .= '</td>' . DOKU_LF;
        $out .= '<td><label>'.$this->getLang('mon').':</label></td>' . DOKU_LF;
        $out .= '<td><div class="input">' . DOKU_LF;
        $out .= '<select id="month" name="plugin_wikicalendar_month">' . DOKU_LF;
 
        for($i=1;$i<=12;$i++) {
            if($i == $this->showMonth) {
                $out .= '<option value="'.$i.'" selected="selected">'.$this->langMonth[$i].'</option>' . DOKU_LF;
            } else {
                $out .= '<option value="'.$i.'">'.$this->langMonth[$i].'</option>' . DOKU_LF;
            }
        }
 
        $out .= '</select>' . DOKU_LF;
        $out .= '</div>' . DOKU_LF;
        $out .= '</td>' . DOKU_LF;
        $out .= '<td><input type="hidden" name="id" value="'.$ID.'" />' . DOKU_LF;
        $out .= '<input type="submit" class="button" name="go2" value="'.$this->getLang('go').'" />' . DOKU_LF;
        $out .= '</form>' . DOKU_LF;
 
        if($this->showYear != $this->curDate['year'] or $this->showMonth != $this->curDate['mon']) {
            $out .= '<form action="'.script().'" method="post" class="go2" onsubmit="">' . DOKU_LF;
            $out .= '<input type="hidden" name="id" value="'.$ID.'" />' . DOKU_LF;
            $out .= '<input type="submit" class="button" name="back2cur" value="'.$this->getLang('current').'" />' . DOKU_LF;
            $out .= '</form>' . DOKU_LF;
        } else {
            $out .= '</td></tr>' . DOKU_LF;
        }
 
        $out .= '</table>' . DOKU_LF;
 
        return ($out);
    }
}
//Setup Vim: tabstop=4 enc=utf8
