<?php
/**
 * Configuration metadata for DokuWiki Plugin Wikicalendar
 * @author Michael Klier <chi@chimeric.de>
 */
$meta['weekstart'] = array('multichoice', '_choices' => array('Monday', 'Sunday'));
$meta['timezone']  = array('multichoice', '_choices' => timezone_identifiers_list());
