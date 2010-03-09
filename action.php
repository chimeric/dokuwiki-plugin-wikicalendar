<?php
/**
 * DokuWiki Action Plugin WikiCalendar
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Michael Klier <chi@chimeric.de>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');
if(!defined('DOKU_LF')) define('DOKU_LF', "\n");

require_once(DOKU_PLUGIN.'action.php');

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class action_plugin_wikicalendar extends DokuWiki_Action_Plugin {

    function getInfo() {
        return array(
                'author' => 'Michael Klier',
                'email'  => 'chi@chimeric.de',
                'date'   => @file_get_contents(DOKU_PLUGIN.'wikicalendar/VERSION'),
                'name'   => 'WikiCalendar Plugin (action component)',
                'desc'   => 'Implements a simple Calendar with links to wikipages.',
                'url'    => 'http://dokuwiki.org/plugin:wikicalendar',
            );
    }

    // register hook
    function register(&$controller) {
        $controller->register_hook('ACTION_SHOW_REDIRECT', 'BEFORE', $this, 'handle_redirect');
        $controller->register_hook('HTML_EDITFORM_OUTPUT', 'BEFORE', $this, 'handle_form');
        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'handle_started');
    }

    /**
     * Checks for calendar values for proper redirects
     */
    function handle_started(&$event, $param) {
        if(is_array($_SESSION[DOKU_COOKIE])) {
            if(array_key_exists('plugin_wikicalendar_month', $_SESSION[DOKU_COOKIE])) {
                $_REQUEST['plugin_wikicalendar_month'] = $_SESSION[DOKU_COOKIE]['plugin_wikicalendar_month'];
                $_REQUEST['plugin_wikicalendar_year']  = $_SESSION[DOKU_COOKIE]['plugin_wikicalendar_year'];
                unset($_SESSION[DOKU_COOKIE]['plugin_wikicalendar_month']);
                unset($_SESSION[DOKU_COOKIE]['plugin_wikicalendar_year']);
            }
        }
    }

    /**
     * Inserts the hidden redirect id field into edit form
     */
    function handle_form(&$event, $param) {
        if(array_key_exists('plugin_wikicalendar_redirect_id', $_REQUEST)) {
            $event->data->addHidden('plugin_wikicalendar_redirect_id', cleanID($_REQUEST['plugin_wikicalendar_redirect_id']));
            $event->data->addHidden('plugin_wikicalendar_month', cleanID($_REQUEST['plugin_wikicalendar_month']));
            $event->data->addHidden('plugin_wikicalendar_year', cleanID($_REQUEST['plugin_wikicalendar_year']));
        }
    }

    /**
     * Redirects to the calendar page
     */
    function handle_redirect(&$event, $param) {
        if(array_key_exists('plugin_wikicalendar_redirect_id', $_REQUEST)) {
            @session_start();
            $_SESSION[DOKU_COOKIE]['plugin_wikicalendar_month'] = $_REQUEST['plugin_wikicalendar_month'];
            $_SESSION[DOKU_COOKIE]['plugin_wikicalendar_year']  = $_REQUEST['plugin_wikicalendar_year'];
            @session_write_close();
            $event->data['id'] = cleanID($_REQUEST['plugin_wikicalendar_redirect_id']);
            $event->data['title'] = '';
        }
    }
}

// vim:ts=4:sw=4:et:enc=utf-8:
