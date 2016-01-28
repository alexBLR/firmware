<?PHP
/*
?Á ÏÓÎÏ×Õ ÂÙÌ ×Ú?Ô ÓËÒÉÐÔ:
XeoPort (mailserver-2-mysql-import-module)
(c) 2002 xeoman
http://xeoman.com/code/php/xeoport

?Ì? ÎÁÓÔÒÏÊËÉ ÓËÒÉÐÔÁ (ÄÏÓÔÕÐÙ, ÓÅÒ×ÅÒ)  ÎÅÏÂÈÏÄÉÍÏ ×ÎÏÓÉÔØ ÉÚÍÅÎÅÎÉ? × ÆÁÊÌ "config.inc"
*/
$my_path = dirname(__FILE__);
$absolute_path = dirname($my_path);

// Set up the appropriate CMS framework
define('_JEXEC', 1);
define('JPATH_BASE', $absolute_path);
define('DS', DIRECTORY_SEPARATOR);

// Load the framework
require_once (JPATH_BASE . DS . 'includes' . DS . 'defines.php');
require_once (JPATH_BASE . DS . 'includes' . DS . 'framework.php');

include('includes/configure.inc');
include('includes/functions.inc');
include('../components/com_brief/brief.config.php');

/* 
?ÓÔÁÎÏ×ËÁ ÌÉÍÉÔÁ ×ÒÅÍÅÎÉ ÒÁÂÏÔÙ ÓËÒÉÐÔÁ. ?ÁÓÔÒÁÉ×ÁÔØ ÎÅÏÂÈÏÄÉÍÏ × ÆÁÊÌÅ config.inc ÐÅÒÅÍÅÎÎÁ? time_limit
?ÇÒÉÁÎÉÞÅÎÉÅ: ÎÅ ÂÕÄÅÔ ÒÁÂÏÔÁÔØ, ÅÓÌÉ PHP ÚÁÐÕÝÅÎ × ÚÁÝÉÝ?ÎÎÏÍ ÒÅÖÉÍÅ
*/
set_time_limit($time_limit);
/* preparing header of feedback page */

$html_output = '<?xml version="1.0" encoding="iso-8859-1"?>' . "\r\n";
$html_output .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\r\n";
$html_output .= '<html xmlns="http://www.w3.org/1999/xhtml">' . "\r\n";
$html_output .= '<head><title> Mailer ' . $version_nr . '</title>' . "\r\n";
$html_output .= '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />' . "\r\n";
$html_output .= '<meta name="author" content="Mailer" />' . "\r\n";
$html_output .= '<meta name="copyright" content="2002 Xeoman" />' . "\r\n";
$html_output .= '<meta name="robots" content="index, follow" />' . "\r\n";
$html_output .= '<meta name="keywords" content="Mailer import IMAP POP3 email messages PHP MySQL module Xeoman" />' . "\r\n";
$html_output .= '<meta name="description" content="Mailer ' . $version_nr . ' - an PHP script to import and backup IMAP or POP3 email messages into MySQL." />' . "\r\n";
$html_output .= '<meta name="MSSmartTagsPreventParsing" content="TRUE" />' . "\r\n";
$html_output .= '<meta http-equiv="imagetoolbar" content="no" />' . "\r\n";
$html_output .= '<link href="includes/xeoport.css" rel="stylesheet" type="text/css" />' . "\r\n";
$html_output .= '</head><body>' . "\r\n";
$html_output .= '<table width="100%" border="0" cellpadding="3" cellspacing="1">' . "\r\n";
$html_output .= '<tr class="report"><th colspan="5" class="report"><br /><h1 class="report">Mailer ' . $version_nr . '</h1><br /></th></tr>' . "\r\n";
$html_output .= '<tr class="status"><td colspan="5" class="status"><p>Mailer started on ' . date("Y-m-d [H:i:s]") . '</p>' . "\r\n";

/* 
?ÔËÒÙÔØ ÓÏÅÄÉÎÅÎÉÅ Ó ÐÏÞÔÏ×ÙÍ ÓÅÒ×ÅÒÏÍ. ?ÏÅÄÉÎÅÎÉÅ × ÐÅÒÅÍÅÎÎÏÊ $inbox. 
?ÁÓÔÒÏÊËÉ ÓÅÒ×ÅÒÁ, ÐÒÏÔÏËÏÌÁ, ÐÏÌØÚÏ×ÁÔÅÌ? É ÐÁÒÏÌ? × ÆÁÊÌÅ config.inc
*/

$inbox = @imap_open('{' . $mail_host . '/' . $mail_protocol . '}', $mail_user, $mail_pass);
if (!$inbox) {
    /* okay we give some feedback now */
    if ($html_feedback == TRUE) {
        $html_output .= '<p class="status">XeoPort could not connect: <strong>' . imap_last_error() . '</strong></p></td></tr>' . "\r\n";
        $html_output .= '</table></body></html>';
        echo $html_output;
        $html_output = NULL;
    }
    @imap_close($inbox);
    throw new Exception("inbox not oppened");
}
$html_output .= '<p class="status">Mailer sucessfully connected to <strong>' . $mail_host . '</strong></p>' . "\r\n";

/*
count messages and exit if inbox is empty 
?ÞÉÔÁÅÍ ËÏÌÉÞÅÓÔ×Ï ÓÏÏÂÝÅÎÉÊ × ÐÏÞÔÏ×ÏÍ ?ÝÉËÅ. —ÓÌÉ ÓÏÏÂÝÅÎÉÊ ÎÅÔ, ÔÏ ÚÁËÁÎÞÉ×ÁÅÍ, ÚÁËÒÙ×ÁÅÍ ËÏÎÎÅËÔ $inbox ÐÏ imap 
É ÚÁËÁÎÞÉ×ÁÅÍ ÒÁÂÏÔÕ ÓËÒÉÐÔÁ. šÏÌÉÞÅÓÔ×Ï ÓÏÏÂÝÅÎÉÊ × ÐÅÒÅÍÅÎÎÏÊ $total
*/

$total = @imap_num_msg($inbox);

if ($total < 1) {
    /* okay we give some feedback now */
    if ($html_feedback == TRUE) {
        $html_output .= '<p class="status">No messages found on <strong>' . $mail_host . '</strong>. Disconnected on ' . date("Y-m-d [H:i:s]") . '</p></td></tr>' . "\r\n";
        $html_output .= '</table></body></html>';
        echo $html_output;
        $html_output = NULL;
    }
    @imap_close($inbox);
    throw new Exception("no message found");
}

/* open mysql-link or exit if we can not / echo result / create table 
?ÔËÒÙ×ÁÅÍ ÌÉÎË Ë ÂÁÚÅ ÄÁÎÎÙÈ. ?ÁÓÔÒÏÊËÉ ÄÏÓÔÕÐÁ Ë ÂÁÚÅ ËÏÎÆÉÇÕÒÉÒÏ×ÁÔØ × ÆÁÊÌÅ config.inc
?ÉÎË × ÐÅÒÅÍÅÎÎÏÊ $sql_link
*/


$sql_link = mysql_connect("$sql_host", "$sql_user", "$sql_pass")
    or die('not connect');
mysql_select_db("$sql_db");

$html_output .= '<p class="status">Mailer sucessfully connected to database <strong>' . $sql_db . '</strong></p>' . "\r\n";


/* check if table exists if not create it
?ÒÏ×ÅÒËÁ ÎÁÌÉÞÉ? ÔÁÂÌÉÃÙ. —ÓÌÉ ÎÅÔ ÔÁÂÌÉÃÙ, ÔÏ ÓÏÚÄÁ?Í.
*/


$sql_table_ok = @mysql_num_rows(@mysql_query("SELECT * FROM $sql_table"));

/* ×Ù×ÅÓÔÉ ÓÔÁÔÕÓ ÓÅÌÅËÔÁ 
$html_output .= "$sql_table_ok";
echo $html_output;
*/

/*if ($sql_table_ok == NULL) {*/
if ($sql_table_ok == FALSE) {
    $sql_createtable = 'CREATE TABLE ' . $sql_table . ' (
  xp_nr int(11) NOT NULL auto_increment,
  xp_id tinytext,
  xp_updated bigint(20) default NULL,
  xp_md5 tinytext,
  xp_time_unix bigint(20) default NULL,
  xp_time_iso time default NULL,
  xp_date_iso date default NULL,
  xp_date_full tinytext,
  xp_from_name tinytext,
  xp_from_address tinytext,
  xp_from_full tinytext,
  xp_from_replyto tinytext,
  xp_to_name tinytext,
  xp_to_address tinytext,
  xp_to_full tinytext,
  xp_subject_text text,
  xp_subject_inreplyto tinytext,
  xp_header_raw longtext,
  xp_body_raw longtext,
  xp_body_text longtext,
  xp_attachments tinytext,
  xp_size smallint(6) default NULL,
  xp_type tinytext,
  PRIMARY KEY  (xp_nr)
) TYPE=MyISAM;';
    $sql_ok = @mysql_query($sql_createtable);
    /*if ($sql_ok != NULL) {*/
    if ($sql_ok == TRUE) {
        $html_output .= '<p class="status">Table <strong>' . $sql_table . '</strong> successfully created</p>' . "\r\n";
    } else {
        $html_output .= '<p class="status">Table <strong>' . $sql_table . '</strong> could NOT be created<br />Please create it manually<br />XeoPort will exit now.</p></td></tr>' . "\r\n";
        $html_output .= '</table></body></html>';
        /* okay we give some feedback now */
        /*if ($html_feedback != NULL) {*/
        if ($html_feedback == TRUE) {
            echo $html_output;
        }
        exit;
    }
}

/* 
ÅÓÌÉ ÔÁÂÌÉÃÁ  ÎÅ ÓÏÚÄÁÎÁ ÉÌÉ ÓÕÝÅÓÔ×ÕÅÔ, ÔÏ ÏÐÒÅÄÅÌ?ÅÍ, ÐÉÓÁÔØ ×Ó? ÓÏÏÂÝÅÎÉÅ ÉÌÉ ÔÏÌØËÏ ÔÅËÓÔ × ÂÁÚÕ. 
?ÐÒÅÄÅÌ?ÅÔÓ? ÐÅÒÅÍÅÎÎÏÊ $insert_raw × ÆÁÊÌÅ config.inc
*/

if ($insert_raw == TRUE) {
    $html_output .= '<p class="status">All headers and the entire message will be imported into <strong>' . $sql_db . '</strong></p></td></tr>' . "\r\n";
} else {
    $html_output .= '<p class="status">Only the text part of messages will be imported into <strong>' . $sql_db . '</strong></p></td></tr>' . "\r\n";
}
/* okay we give some feedback now */
if ($html_feedback == TRUE) {
    echo $html_output;
    $html_output = NULL;
}

/* 
?ËÌÀÞÁÅÍ ÔÁÊÍÅÒ ÒÁÂÏÔÙ ×ÓÅÇÏ ÓËÒÉÐÔÁ. ?ÕÎËÃÉ? tumer_start ÏÐÉÓÁÎÁ × functions.inc É
×ÏÚ×ÒÁÝÁÅÔ ???ÔÅËÕÝÅÅ ×ÒÅÍ????
timer for the entire script
 */

$time_looper = timer_start();

/*
now we actually loop thru the messages on server 
?ÁÐÕÓË ÃÉËÌÁ ÞÔÅÎÉ? ×ÓÅÈ ÓÏÏÂÝÅÎÉÊ ËÏÔÏÒÙÅ ÅÓÔØ ÎÁ ÓÅÒ×ÅÒÅ × ÐÏÞÔÏ×ÏÍ ?ÝÉËÅ. 
šÏÌÉÞÅÓÔ×Ï ÃÉËÌÏ× ÏÐÒÅÄÅÌÅÎÏ ËÏÌÉÞÅÓÔ×ÏÍ ÓÏÏÂÝÅÎÉÊ × ÐÅÒÅÍÅÎÎÏÊ $total 
*/

for ($x = 0; $x <= $total; $x++) {
    $time_scriptstart = timer_start();

    /* first all vars from the previous mail got killed */
    $html_output = NULL;
    $structure = NULL;
    $headers = NULL;
    $xp_id = NULL;
    $xp_md5 = NULL;
    $xp_time_unix = NULL;
    $xp_date_full = NULL;
    $xp_subject_text = NULL;
    $xp_subject_inreplyto = NULL;
    $xp_from_full = NULL;
    $xp_from_name = NULL;
    $xp_from_address = NULL;
    $xp_from_replyto = NULL;
    $xp_to_full = NULL;
    $xp_to_name = NULL;
    $xp_to_address = NULL;
    $xp_header_raw = NULL;
    $xp_body_raw = NULL;
    $xp_body_text = NULL;
    $xp_attachments = NULL;
    $xp_size = NULL;
    $xp_type = NULL;
    $parts_type = NULL;
    $parts_encoding = NULL;
    $parts_size = NULL;
    $parts_filename = NULL;
    $parts_filesize = NULL;
    $parts_structure = NULL;
    $sql_ok = NULL;
    $temp_html_key = NULL;
    $temp_p = NULL;
    $temp_b = NULL;
    $temp_s = NULL;
    $temp_t = NULL;
    $temp_y = NULL;
    $temp_z = NULL;
    $temp_k = NULL;
    $temp_v = NULL;

    /* get header and structure */
    $headers = imap_header($inbox, $x);
    $structure = imap_fetchstructure($inbox, $x);

    /* initiate most of our vars */
    $xp_id = $headers->message_id;
    $xp_time_unix = $headers->udate;
    $xp_time_iso = date("H:i:s", $xp_time_unix);
    $xp_date_iso = date("Y-m-d", $xp_time_unix);
    $xp_date_full = $headers->Date;
    $xp_subject_text = decode_header($headers->subject);
    if (strlen($xp_subject_text) > 30 && strpos(substr($xp_subject_text, 0, 30), " ") < 1) {
        $xp_subject_show = substr($xp_subject_text, 0, 30) . ' ' . substr($xp_subject_text, 31);
    } else {
        $xp_subject_show = $xp_subject_text;
    }
    $xp_subject_inreplyto = $headers->in_reply_to;
    $xp_from_full = decode_header($headers->fromaddress);
    $xp_from_address = get_substring($xp_from_full, '<', '>');
    $xp_from_name = get_name($xp_from_full);
    $xp_from_replyto = decode_header($headers->reply_toaddress);
    $xp_to_full = decode_header($headers->toaddress);
    $xp_to_address = get_substring($xp_to_full, '<', '>');
    $xp_to_name = get_name($xp_to_full);

    /* leave the imap-prefs-file alone */
    if (substr_count($xp_from_name, "Mail System Internal Data") > 0) {
        continue;
    }
    /* construct message-id if missing */
    if (!$xp_id) {
        $xp_id = md5(imap_fetchheader($inbox, $x));
    }


    $attachments = array();
    //print_r($structure);
    if (isset($structure) && count($structure)) {
        for ($i = 0; $i < count($structure); $i++) {
            //echo 19;
            $attachments[$i] = array(
                'is_attachment' => false,
                'filename' => '',
                'name' => '',
                'attachment' => '',
                'ver' => ''
            );

            if ($structure->parts[$i]->ifdparameters) {
                $attachments[$i]['bytes'] = $structure->parts[$i]->bytes;
                foreach ($structure->parts[$i]->dparameters as $object) {
                    if (strtolower($object->attribute) == 'filename') {
                        $attachments[$i]['is_attachment'] = true;
                        $attachments[$i]['filename'] = $object->value;
                    }
                }
            }

            if ($structure->parts[$i]->ifparameters) {
                $attachments[$i]['bytes'] = $structure->parts[$i]->bytes;
                foreach ($structure->parts[$i]->parameters as $object) {
                    if (strtolower($object->attribute) == 'name') {
                        $attachments[$i]['is_attachment'] = true;
                        $attachments[$i]['name'] = $object->value;
                    }
                }
            }

            if ($structure->ifparameters) {
                $attachments[$i]['bytes'] = $structure->bytes;
                foreach ($structure->parameters as $object) {
                    if (strtolower($object->attribute) == 'name') {
                        $attachments[$i]['is_attachment'] = true;
                        $attachments[$i]['name'] = $object->value;
                        $attachments[$i]['ver'] = 1;
                    }
                }
            }

            if ($attachments[$i]['is_attachment']) {
                $attachments[$i]['attachment'] = imap_fetchbody($inbox, $x, $i + 1);
                if($attachments[$i]['ver'] == 1) {
                    if ($structure->encoding == 3) {  // 3 = BASE64
                        $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                    } elseif ($structure->encoding == 4) { // 4 = QUOTED-PRINTABLE
                        $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                    }
                } else {
                    if ($structure->parts[$i]->encoding == 3) {  // 3 = BASE64
                        $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                    } elseif ($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                        $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                    }
                }

            }
        }
    }
    echo $x . "\n";
    foreach ($attachments as $key => $attachment) {
        if ($attachment['ver'] != 1) {
            $name = explode("?", $attachment['name']);
            $name = mb_convert_encoding($name[3], "UTF-8", "BASE64");
            $name = explode(".", $name);
        } else {
            $name = explode(".", $attachment['name']);
        }
        $contents = $attachment['attachment'];
        $bytes = $attachment['bytes'];

        if (!empty($name[0]) and isset($contents)) {
            UploadFile($contents, $xp_subject_text, $name, $bytes);
        }
    }


    /* unified id consisting only of ascii for later callback*/
    $xp_md5 = md5($xp_id);

    /*__________________ here we decide if we insert the email into database or skip that part  ___________________________*/

    $html_output .= '<tr class="col">' . "\r\n";
    $html_output .= '<td class="col1nr">' . $x . ' / ' . $total . '</td>' . "\r\n";
    $html_output .= '<td class="col2subject"><em>' . htmlspecialchars($xp_from_name) . '</em> ==&gt; <em>' . htmlspecialchars($xp_to_name) . '</em><br /><strong>' . htmlspecialchars($xp_subject_show) . '</strong></td>' . "\r\n";

    $counter_rows = mysql_num_rows(mysql_query("SELECT * from $sql_table WHERE xp_id='$xp_id'"));
    if ($counter_rows > 0) {
        /* if the mail is already in the database skip the rest */
//		mysql_query("UPDATE $sql_table SET xp_id_pulled = '1' WHERE xp_id='$xp_id'");
        $counter_found++;
        $html_output .= '<td class="col3parts">&nbsp;</td>' . "\r\n";
        $html_output .= '<td class="col4indatabase">in database</td>' . "\r\n";
        $html_output .= '<td class="col5timer">' . number_format(timer_stop($time_scriptstart), 2, ',', '.') . '</td></tr>' . "\r\n";
        /* okay we give some feedback now */
        if ($html_feedback == TRUE) {
            echo $html_output;
            $html_output = NULL;
        }
        /* and delete the message from inbox or not according to prefs */
        if ($message_delete == TRUE) {
            imap_delete($inbox, $x);
        }
        continue;
    }

    /*__________________ get the structure of the mail, count its parts and split it up  ___________________________*/

    $xp_header_raw = imap_fetchheader($inbox, $x);
    $parts = $structure->parts;
    $parts_count = count($parts);

    /*__________________  loop thru all parts and subparts of message and build arrays accordingly ___________________________________*/

    for ($temp_z = 0; $temp_z < $parts_count; $temp_z++) {
        $temp_p = NULL;
        $temp_b = NULL;
        $parts_type_main = NULL;
        $parts_subtype_main = NULL;

        if ($parts[$temp_z]->type == "") {
            $parts[$temp_z]->type = 0;
        }
        $temp_y = $temp_z + 1;
        $parts_number = '_' . $temp_y;
        $parts_type_main = strtolower($type[$parts[$temp_z]->type]);
        $parts_type["$parts_number"] = $parts_type_main . '/' . strtolower($parts[$temp_z]->subtype);
        $parts_encoding["$parts_number"] = $encoding[$parts[$temp_z]->encoding];
        $parts_size["$parts_number"] = $parts[$temp_z]->bytes;
        if (strtolower($parts[$temp_z]->disposition) == "attachment") {
            $temp_b = $parts[$temp_z]->dparameters;
            if (is_array($temp_b) || is_object($temp_b)) {
                reset($temp_b);
                while (list(, $temp_p) = each($temp_b)) {
                    if ($temp_p->attribute == "FILENAME") {
                        $xp_attachments .= decode_header($temp_p->value) . ' [' . ceil($parts[$temp_z]->bytes / 1024) . ' KB]' . $line_break;
                        $parts_filename["$parts_number"] = decode_header($temp_p->value);
                        $parts_filesize["$parts_number"] = $parts[$temp_z]->bytes;
                    }
                }
            }
        }
        /* if there are inline parts dig deeper */
        if ($parts_type_main == 'multipart') {
            $parts_sub = $parts[$temp_z]->parts;
            $parts_sub_count = count($parts_sub);
            for ($temp_s = 0; $temp_s < $parts_sub_count; $temp_s++) {
                $temp_t = $temp_s + 1;
                $parts_sub_number = $parts_number . '.' . $temp_t;
                $parts_subtype_main = strtolower($type[$parts_sub[$temp_s]->type]);
                $parts_type["$parts_sub_number"] = $parts_subtype_main . '/' . strtolower($parts_sub[$temp_s]->subtype);
                $parts_encoding["$parts_sub_number"] = strtolower($encoding[$parts_sub[$temp_s]->encoding]);
                $parts_size["$parts_sub_number"] = $parts_sub[$temp_s]->bytes;
                /* 3level parts are rare but we want to be sure */
                if ($parts_subtype_main == 'multipart') {
                    $parts_subsub = $parts_sub[$temp_s]->parts;
                    $parts_subsub_count = count($parts_subsub);
                    for ($temp_m = 0; $temp_m < $parts_subsub_count; $temp_m++) {
                        $temp_n = $temp_m + 1;
                        $parts_subsub_number = $parts_sub_number . '.' . $temp_n;
                        $parts_type["$parts_subsub_number"] = strtolower($type[$parts_subsub[$temp_m]->type]) . '/' . strtolower($parts_subsub[$temp_m]->subtype);
                        $parts_encoding["$parts_subsub_number"] = strtolower($encoding[$parts_subsub[$temp_m]->encoding]);
                        $parts_size["$parts_subsub_number"] = $parts_subsub[$temp_m]->bytes;
                    }
                }
            }
        }

    }
    /*__________________  get the parts of the message we want _____________________________________________________*/

    if (is_array($parts_type)) {
        while (list ($key, $val) = each($parts_type)) {
            if (strlen($key) < 3) {
                $parts_structure .= '<strong>' . str_replace("_", "", $key) . '</strong>';
            } else {
                $parts_structure .= '&nbsp;&nbsp;&nbsp;<strong>' . str_replace("_", "", $key) . '</strong>';
            }
            $parts_structure .= ' _ ' . $val . ' <em>' . $parts_encoding[$key] . ' _ </em> [' . $parts_size[$key] . ']<br />';
            if ($val == 'text/plain' || $val == 'message/rfc822') {
                $xp_body_text = decode_text(imap_fetchbody($inbox, $x, str_replace("_", "", $key)), $parts_encoding[$key]);
            }
            /* we need this just in case message has only html-part */
            if ($val == 'text/html') {
                $temp_html_key = $key;
            }
        }
        /* if the array is empty there's only text so we can simply get the body-part */
    } else {
        /* decode if body is encoded */
        if ($structure->encoding > 0) {
            $xp_body_text = decode_text(imap_body($inbox, $x), $encoding[$structure->encoding]);
            $parts_structure .= '<strong>0</strong> _ text/plain <em>' . $encoding[$structure->encoding] . '</em> _ [' . $structure->bytes . ']<br />';
        } else {
            $xp_body_text = imap_body($inbox, $x);
            $parts_structure .= '<strong>0</strong> _ text/plain <em>7bit</em> _ [' . $structure->bytes . ']<br />';
        }
    }
    /* if we have no text till now we try to check for the html-part */
    if (($xp_body_text == '') && ($temp_html_key)) {
        $xp_body_text = strip_tags(decode_text(imap_fetchbody($inbox, $x, str_replace("_", "", $temp_html_key)), $parts_encoding[$temp_html_key]));
    }

    /* the raw email will be saved or not according to prefs */
    if ($insert_raw == TRUE) {
        $xp_header_raw = imap_fetchheader($inbox, $x);
        $xp_body_raw = imap_body($inbox, $x);
    }
    /* replacing line breaks according to prefs */
    $xp_body_text = preg_replace("/(\015\012)|(\015)|(\012)/", "$line_break", $xp_body_text);
    $xp_attachments = str_replace("$line_break$line_break", "$line_break", $xp_attachments);
    /* calculating the message size */
    if (is_array($parts_size)) {
        $xp_size = ceil(array_sum($parts_size) / 1024);
    } else {
        $xp_size = ceil($structure->bytes / 1024);
    }

    /* this will make all data safe for mysql */
    if ($conf_magicquotes == 0) {
        foreach ($GLOBALS as $temp_k => $temp_v) {
            if (substr_count($temp_k, "xp_") > 0) {
                $GLOBALS[$temp_k] = addslashes($temp_v);
            }
        }
    }

    if ($xp_body_text == '') {
        $counter_empty++;
        $html_output .= '<td class="col3parts">' . $parts_structure . '</td>' . "\r\n";
        $html_output .= '<td class="col4empty">empty</td>' . "\r\n";
        $html_output .= '<td class="col5timer">' . number_format(timer_stop($time_scriptstart), 2, ',', '.') . '</td></tr>' . "\r\n";
        /* okay we give some feedback now */
        if ($html_feedback == TRUE) {
            echo $html_output;
            $html_output = NULL;
        }
    }

    $sql_insertstring = "INSERT INTO $sql_table(xp_id, xp_md5, xp_time_unix, xp_time_iso, xp_date_iso, xp_date_full, xp_from_name, xp_from_address, xp_from_full, xp_from_replyto, xp_to_name, xp_to_address, xp_to_full, xp_subject_text, xp_subject_inreplyto, xp_header_raw, xp_body_raw, xp_body_text, xp_attachments, xp_size, xp_type) VALUES ('$xp_id', '$xp_md5', '$xp_time_unix', '$xp_time_iso', '$xp_date_iso', '$xp_date_full', '$xp_from_name', '$xp_from_address', '$xp_from_full', '$xp_from_replyto', '$xp_to_name', '$xp_to_address', '$xp_to_full', '$xp_subject_text', '$xp_subject_inreplyto', '$xp_header_raw', '$xp_body_raw', '$xp_body_text', '$xp_attachments', '$xp_size', '$xp_type')";

    /* check if everything went smooth */
    $sql_ok = mysql_query($sql_insertstring);
    if ($sql_ok == TRUE) {
        $counter_inserted++;
        $counter_size += $xp_size;
        $html_output .= '<td class="col3parts">' . $parts_structure . '</td>' . "\r\n";
        $html_output .= '<td class="col4inserted">inserted</td>' . "\r\n";
        $html_output .= '<td class="col5timer">' . number_format(timer_stop($time_scriptstart), 2, ',', '.') . '</td></tr>' . "\r\n";
        /* okay we give some feedback now */
        if ($html_feedback == TRUE) {
            echo $html_output;
            $html_output = NULL;
        }
        if ($message_delete == TRUE) {
            imap_delete($inbox, $x);
        }
    } elseif ($sql_ok == FALSE) {
        $counter_sqlerrors++;
        $html_output .= '<td class="col3parts">' . $parts_structure . '</td>' . "\r\n";
        $html_output .= '<td class="col4failed">failed to insert</td>' . "\r\n";
        $html_output .= '<td class="col5timer">' . number_format(timer_stop($time_scriptstart), 2, ',', '.') . '</td></tr>' . "\r\n";
        /* okay we give some feedback now */
        if ($html_feedback == TRUE) {
            echo $html_output;
            $html_output = NULL;
        }
    }
    /* we're thru with all the messages */
}

/* stop the timer for the script */
$time_counter = timer_stop($time_looper);

/* gather some final information about errors, inserts etc */
$html_output .= '<tr class="status"><td colspan="5" class="status">' . "\r\n";
$html_output .= '<p class="status">' . $total . ' messages on <strong>' . $mail_host . '</strong></p>' . "\r\n";
$html_output .= '<p class="status">Already in database: <strong>' . $counter_found . '</strong></p>' . "\r\n";
$html_output .= '<p class="status">Inserted into database:  <strong>' . $counter_inserted . '</strong></p>' . "\r\n";
$html_output .= '<p class="status">MySql-errors (not inserted): <strong>' . $counter_sqlerrors . '</strong></p>' . "\r\n";
$html_output .= '<p class="status">Empty messages: <strong>' . $counter_empty . '</strong></p>' . "\r\n";
$html_output .= '<p class="status">Overall processing time: <strong>' . round($time_counter, 1) . '</strong> sec</p>' . "\r\n";
$html_output .= '<p class="status">Seconds per message: <strong>' . round(($time_counter / $x), 3) . '</strong></p></td></tr>' . "\r\n";
$html_output .= '</table></body></html>';

/* give some final feedback */
if ($html_feedback == TRUE) {
    echo $html_output;
    $html_output = NULL;
}

/* close mysql-connection */
mysql_close($sql_link);

/* kill all messages marked as deleted on server and/ or close connection */
if ($message_delete == TRUE) {
    imap_close($inbox, CL_EXPUNGE);
}
imap_close($inbox);


function UploadFile($contents, $id, $name, $bytes)
{

    $mainframe =& JFactory::getApplication('site');
    //$my        =& JFactory::getUser();
    $database =& JFactory::getDBO();
    //echo $id."\n";
    $query = ' SELECT u.id'
        . ' FROM jos_users u'
        . ' WHERE u.username = \'' . $id . '\'';
    $database->setQuery($query);
    $idUser = $database->loadResultArray();
    $user_id = $idUser[0];
    if (!isset($user_id) or $user_id == 0) {
        $user_id = 103;
    }
    echo $user_id . "\n";
    echo $name[0] . "." . $name[1] . "-3\n\n";
    echo $bytes . "-3\n\n";
    //return 1;
    $default_owner = 62;

    $database->setQuery("select sum(size) from #__brief_files where ownerid='$default_owner'");
    $totalsize = $database->loadResult();

    $sql = "SELECT DISTINCT category FROM #__brief_admin_category ORDER BY category ASC";
    $database->setQuery($sql);

    if (!$result = $database->query()) {
        die($database->stderr());
    }

    $categories = array();

    $categoriesdb = $database->loadResultArray(0);
    array_push($categories, WORD4UNSORTED);
    //---------------------------------------------

    if (!empty($categoriesdb)) {
        $categories = array_merge($categories, $categoriesdb);
    }

    $fileNo = JArrayHelper::getValue($_REQUEST, 'fileno', 0);

    $uploadErrorArray = array();
    $uploadNoErrorArray = array();
    $extensions = explode(',', BRIEF_ALLOWED_EXTENSIONS);


    if (count($contents) > 0) {

        $i = $patterns[1];

        $userFile = $name[0] . "." . $name[1];
        $userFileExtension = $name[1];
        $userFileSize = round($bytes);
        $theFileSize = round($bytes / 1024);
        settype($theFileSize, "integer");

        // Check if extension is allowed
        if (!empty($extensions[0])) {
            $extension = strtolower(array_pop(explode('.', $userFile)));

        }

        if ((MAX_FILE_SIZE == "0") ||
            (($theFileSize <= MAX_FILE_SIZE || ($ImAdmin && BRIEF_OVERRIDE_MAXIMUM_DISK_SIZE)) &&
                ($totalsize + $theFileSize <= BRIEF_MAX_PER_USER || ($ImAdmin && BRIEF_OVERRIDE_MAXIMUM_DISK_SIZE)))
        ) {
            echo "from if\n";
            if (!is_dir(UPLOAD_FOLDER)) {
                mkdir(UPLOAD_FOLDER, 0755);
            }

            /* if (!is_dir(UPLOAD_FOLDER.$user_id)) */
            if (!is_dir(UPLOAD_FOLDER . $default_owner)) {
                mkdir(UPLOAD_FOLDER . $default_owner, 0755);
            }

            $timelimit = JArrayHelper::getValue($_REQUEST, 'date_limit_' . $i, '');
            $category = JArrayHelper::getValue($_REQUEST, 'categories_' . $i, 'unsorted');
            $description = JArrayHelper::getValue($_REQUEST, 'description_' . $i, '');
            $shared = JArrayHelper::getValue($_REQUEST, 'shared_' . $i, 'private');

            if (!BRIEF_ALLOW_PUBLIC_SHARE) {
                $shared = 'private';
            }

            $vodes = JRequest::getVar('vodes_price' . $i, 0, 'POST', 'integer');
            $timefield = "null";

            if ($timelimit != "") {
                $timefield = "'$timelimit'";

                if (!strtotime($timelimit)) {
                    $timefield = "null";
                    array_push($uploadErrorArray, $userFile . BRIEF_ERROR_INVALID_DATE);

                } elseif (strtotime($timelimit) < time()) {
                    $timefield = "null";
                    array_push($uploadErrorArray, $userFile . BRIEF_ERROR_INVALID_DATE);
                }
            }

            $sql = "INSERT INTO #__brief_files (name,parent,ownerid,size,filetype,category,description,hits,shared,timelimit,price)
    				VALUES ('" . $userFile . "', '" . $curvfolder . "', '" . $default_owner . "', '" . $userFileSize . "', '" . $userFileExtension . "', '" . $category . "','$description', '0','" . $shared . "',$timefield,$vodes);";
            $database->setQuery($sql);

            if (!$result = $database->query()) {
                die($database->stderr());
            }

            $cur_fileid = $database->insertid();

            $target_path = UPLOAD_FOLDER . $default_owner . DS . $cur_fileid . '.file';

            if (!file_put_contents($target_path, $contents)) {
                array_push($uploadErrorArray, $userFile . CANT_MOVE_ERROR);
                $sql = "DELETE FROM #__brief_files where id = '" . $cur_fileid . "'";
                $database->setQuery($sql);
                echo $target_path;

                #die();

                if (!$result = $database->query()) {
                    die($database->stderr());
                }
            } else {
                array_push($uploadNoErrorArray, $userFile);

                $sql = "INSERT INTO #__brief_accesses (fileid,userid,timelimit,dwdcount)
    					      VALUES ('" . $cur_fileid . "', '$user_id', null,0)";

                $database->setQuery($sql);
                if (!$result = $database->query()) {
                    die($database->stderr());
                }


                // Autoshare
                if (BRIEF_AUTOSHARE) {
                    // Get the admins
                    $default_admins = explode(',', BRIEF_DEFAULT_ADMINS);

                    foreach ($default_admins as $admin) {
                        $query = ' INSERT'
                            . ' INTO #__brief_accesses (fileid, userid, timelimit, dwdcount)'
                            . ' VALUES ("' . $cur_fileid . '", ' . $admin . ', null, 0)';
                        $database->setQuery($query);
                        $database->query();

                        //sendEmaiNotifications($cur_fileid);
                    }
                }

            }
        } else {
            if (($theFileSize > MAX_FILE_SIZE) &&
                (MAX_FILE_SIZE != "0")
            ) {
                array_push($uploadErrorArray, $userFile . EXCEED_MAXSIZE_ERROR . " " . MAX_FILE_SIZE);
            } else {
                array_push($uploadErrorArray, $userFile . EXCEED_TOTALSIZE_ERROR . " " . BRIEF_MAX_PER_USER);
            }
        }
    } else {
        array_push($uploadErrorArray, BRIEF_NO_UPLOADED_FILE_ERROR);
    }

    if (count($uploadErrorArray) > 0) {
        $path = "index.php?option=com_brief&task=displayerror&Itemid=" . $Itemid . "&curvfolder=" . $curvfolder . "&errormsgs=";

        for ($i = 0; $i < (count($uploadErrorArray) - 1); $i++) {
            $path .= urlencode($uploadErrorArray[$i]) . ",";
        }

        $path .= "," . $uploadErrorArray[count($uploadErrorArray) - 1];
        #$mainframe->redirect($path);
    } else {
        #$mainframe->redirect("index.php?option=com_brief&task=myfiles&Itemid=".$Itemid."&curvfolder=".$curvfolder);
    }
}

function userMayShare()
{
    global $restrictions;

    if (in_array('share', $restrictions)) {
        return false;
    } else {
        return true;
    }
}

?>