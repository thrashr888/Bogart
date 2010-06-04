<?php

namespace Bogart;

class String {
	// Stolen from Wordpress.org source code + others
	// http://photomatt.net/scripts/autop/
	// pault - 7/18/2007
	
	/**
	 * Functions:
	 * - texturize($text)
	 * - clean_pre($text)
	 * - autop($pee, $br = 1)
	 * - seems_utf8($Str)
	 * - specialchars( $text, $quotes = 0 )
	 * - utf8_uri_encode( $utf8_string, $length = 0 )
	 * - remove_accents($string)
	 * - sanitize_file_name( $name )
	 * - sanitize_user( $username, $strict = false )
	 * - sanitize_title($title, $fallback_title = '')
	 * - sanitize_title_with_dashes($title)
	 * - convert_chars($content, $flag = 'obsolete')
	 * - balance_tags( $text )
	 * - format_to_edit($content, $richedit = false)
	 * - zeroise($number,$threshold)
	 * - backslashit($string)
	 * - trailingslashit($string)
	 * - untrailingslashit($string)
	 * - stripslashes_deep($value)
	 * - urlencode_deep($value)
	 * - antispambot($emailaddy, $mailto=0)
	 * - make_clickable($ret)
	 * - rel_nofollow( $text )
	 * - is_email($user_email)
	 * - iso_descrambler($string)
	 * - get_gmt_from_date($string)
	 * - get_date_from_gmt($string)
	 * - iso8601_timezone_to_offset($timezone)
	 * - popuplinks($text)
	 * - sanitize_email($email)
	 * - trim_excerpt($text)
	 * - ent2ncr($text)
	 * - richedit_pre($text)
	 * - clean_url( $url, $protocols = null )
	 * - htmlentities($myHTML)
	 * - js_escape($text)
	 * - make_link_relative( $link )
	 */
	
	public static function texturize($text) {
		$next = true;
		$output = '';
		$curl = '';
		$textarr = preg_split('/(<.*>)/Us', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		$stop = count($textarr);
	
		$static_characters = array_merge(array('---', ' -- ', '--', 'xn&#8211;', '...', '``', '\'s', '\'\'', ' (tm)'), $cockney); 
		$static_replacements = array_merge(array('&#8212;', ' &#8212; ', '&#8211;', 'xn--', '&#8230;', '&#8220;', '&#8217;s', '&#8221;', ' &#8482;'), $cockneyreplace);
	
		$dynamic_characters = array('/\'(\d\d(?:&#8217;|\')?s)/', '/(\s|\A|")\'/', '/(\d+)"/', '/(\d+)\'/', '/(\S)\'([^\'\s])/', '/(\s|\A)"(?!\s)/', '/"(\s|\S|\Z)/', '/\'([\s.]|\Z)/', '/(\d+)x(\d+)/');
		$dynamic_replacements = array('&#8217;$1','$1&#8216;', '$1&#8243;', '$1&#8242;', '$1&#8217;$2', '$1&#8220;$2', '&#8221;$1', '&#8217;$1', '$1&#215;$2');
	
		for ( $i = 0; $i < $stop; $i++ ) {
			$curl = $textarr[$i];
	
			if (isset($curl{0}) && '<' != $curl{0} && $next) { // If it's not a tag
				// static strings
				$curl = str_replace($static_characters, $static_replacements, $curl);
				// regular expressions
				$curl = preg_replace($dynamic_characters, $dynamic_replacements, $curl);
			} elseif (strpos($curl, '<code') !== false || strpos($curl, '<pre') !== false || strpos($curl, '<kbd') !== false || strpos($curl, '<style') !== false || strpos($curl, '<script') !== false) {
				$next = false;
			} else {
				$next = true;
			}
	
			$curl = preg_replace('/&([^#])(?![a-zA-Z1-4]{1,8};)/', '&#038;$1', $curl);
			$output .= $curl;
		}
	
		return $output;
	}
	
	public static function clean_pre($text) {
		$text = str_replace('<br />', '', $text);
		$text = str_replace('<p>', "\n", $text);
		$text = str_replace('</p>', '', $text);
		return $text;
	}
	
	public static function autop($pee, $br = 1) {
		$pee = $pee . "\n"; // just to make things a little easier, pad the end
		$pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);
		// Space things out a little
		$allblocks = '(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|map|area|blockquote|address|math|style|input|p|h[1-6]|hr)';
		$pee = preg_replace('!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee);
		$pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
		$pee = str_replace(array("\r\n", "\r"), "\n", $pee); // cross-platform newlines
		$pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
		$pee = preg_replace('/\n?(.+?)(?:\n\s*\n|\z)/s', "<p>$1</p>\n", $pee); // make paragraphs, including one at the end
		$pee = preg_replace('|<p>\s*?</p>|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace
		$pee = preg_replace('!<p>([^<]+)\s*?(</(?:div|address|form)[^>]*>)!', "<p>$1</p>$2", $pee);
		$pee = preg_replace( '|<p>|', "$1<p>", $pee );
		$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee); // don't pee all over a tag
		$pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee); // problem with nested lists
		$pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
		$pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
		$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee);
		$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);
		if ($br) {
			$pee = preg_replace('/<(script|style).*?<\/\\1>/se', 'str_replace("\n", "<PreserveNewline />", "\\0")', $pee);
			$pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee); // optionally make line breaks
			$pee = str_replace('<PreserveNewline />', "\n", $pee);
		}
		$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee);
		$pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
		if (strpos($pee, '<pre') !== false)
			$pee = preg_replace('!(<pre.*?>)(.*?)</pre>!ise', " stripslashes('$1') .  stripslashes(clean_pre('$2'))  . '</pre>' ", $pee);
		$pee = preg_replace( "|\n</p>$|", '</p>', $pee );
	
		return $pee;
	}
	
	
	public static function seems_utf8($Str) { # by bmorel at ssi dot fr
		for ($i=0; $i<strlen($Str); $i++) {
			if (ord($Str[$i]) < 0x80) continue; # 0bbbbbbb
			elseif ((ord($Str[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
			elseif ((ord($Str[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
			elseif ((ord($Str[$i]) & 0xF8) == 0xF0) $n=3; # 11110bbb
			elseif ((ord($Str[$i]) & 0xFC) == 0xF8) $n=4; # 111110bb
			elseif ((ord($Str[$i]) & 0xFE) == 0xFC) $n=5; # 1111110b
			else return false; # Does not match any model
			for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
				if ((++$i == strlen($Str)) || ((ord($Str[$i]) & 0xC0) != 0x80))
				return false;
			}
		}
		return true;
	}
	
	public static function specialchars( $text, $quotes = 0 ) {
		// Like htmlspecialchars except don't double-encode HTML entities
		$text = str_replace('&&', '&#038;&', $text);
		$text = str_replace('&&', '&#038;&', $text);
		$text = preg_replace('/&(?:$|([^#])(?![a-z1-4]{1,8};))/', '&#038;$1', $text);
		$text = str_replace('<', '&lt;', $text);
		$text = str_replace('>', '&gt;', $text);
		if ( 'double' === $quotes ) {
			$text = str_replace('"', '&quot;', $text);
		} elseif ( 'single' === $quotes ) {
			$text = str_replace("'", '&#039;', $text);
		} elseif ( $quotes ) {
			$text = str_replace('"', '&quot;', $text);
			$text = str_replace("'", '&#039;', $text);
		}
		return $text;
	}
	
	public static function utf8_uri_encode( $utf8_string, $length = 0 ) {
		$unicode = '';
		$values = array();
		$num_octets = 1;
	
		for ($i = 0; $i < strlen( $utf8_string ); $i++ ) {
	
			$value = ord( $utf8_string[ $i ] );
	
			if ( $value < 128 ) {
				if ( $length && ( strlen($unicode) + 1 > $length ) )
					break; 
				$unicode .= chr($value);
			} else {
				if ( count( $values ) == 0 ) $num_octets = ( $value < 224 ) ? 2 : 3;
	
				$values[] = $value;
	
				if ( $length && ( (strlen($unicode) + ($num_octets * 3)) > $length ) )
					break;
				if ( count( $values ) == $num_octets ) {
					if ($num_octets == 3) {
						$unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]) . '%' . dechex($values[2]);
					} else {
						$unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]);
					}
	
					$values = array();
					$num_octets = 1;
				}
			}
		}
	
		return $unicode;
	}
	
	public static function remove_accents($string) {
		if ( !preg_match('/[\x80-\xff]/', $string) )
			return $string;
	
		if (self::seems_utf8($string)) {
			$chars = array(
			// Decompositions for Latin-1 Supplement
			chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
			chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
			chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
			chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
			chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
			chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
			chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
			chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
			chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
			chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
			chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
			chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
			chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
			chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
			chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
			chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
			chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
			chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
			chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
			chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
			chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
			chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
			chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
			chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
			chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
			chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
			chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
			chr(195).chr(191) => 'y',
			// Decompositions for Latin Extended-A
			chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
			chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
			chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
			chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
			chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
			chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
			chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
			chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
			chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
			chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
			chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
			chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
			chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
			chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
			chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
			chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
			chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
			chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
			chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
			chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
			chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
			chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
			chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
			chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
			chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
			chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
			chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
			chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
			chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
			chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
			chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
			chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
			chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
			chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
			chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
			chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
			chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
			chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
			chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
			chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
			chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
			chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
			chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
			chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
			chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
			chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
			chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
			chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
			chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
			chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
			chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
			chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
			chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
			chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
			chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
			chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
			chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
			chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
			chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
			chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
			chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
			chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
			chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
			chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
			// Euro Sign
			chr(226).chr(130).chr(172) => 'E',
			// GBP (Pound) Sign
			chr(194).chr(163) => '');
	
			$string = strtr($string, $chars);
		} else {
			// Assume ISO-8859-1 if not UTF-8
			$chars['in'] = chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
				.chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
				.chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
				.chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
				.chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
				.chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
				.chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
				.chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
				.chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
				.chr(252).chr(253).chr(255);
	
			$chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";
	
			$string = strtr($string, $chars['in'], $chars['out']);
			$double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
			$double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
			$string = str_replace($double_chars['in'], $double_chars['out'], $string);
		}
	
		return $string;
	}
	
	public static function sanitize_file_name( $name ) { // Like sanitize_title, but with periods
		$name = strtolower( $name );
		$name = preg_replace('/&.+?;/', '', $name); // kill entities
		$name = str_replace( '_', '-', $name );
		$name = preg_replace('/[^a-z0-9\s-.]/', '', $name);
		$name = preg_replace('/\s+/', '-', $name);
		$name = preg_replace('|-+|', '-', $name);
		$name = trim($name, '-');
		return $name;
	}
	
	public static function sanitize_user( $username, $strict = false ) {
		$raw_username = $username;
		$username = strip_tags($username);
		// Kill octets
		$username = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '', $username);
		$username = preg_replace('/&.+?;/', '', $username); // Kill entities
	
		// If strict, reduce to ASCII for max portability.
		if ( $strict )
			$username = preg_replace('|[^a-z0-9 _.\-@]|i', '', $username);
	
		return $username;
	}
	
	public static function sanitize_title($title, $fallback_title = '') {
		$title = strip_tags($title);
	
		if (empty($title)) {
			$title = $fallback_title;
		}
	
		return $title;
	}
	
	public static function sanitize_title_with_dashes($title) {
		$title = strip_tags($title);
		// Preserve escaped octets.
		$title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
		// Remove percent signs that are not part of an octet.
		$title = str_replace('%', '', $title);
		// Restore octets.
		$title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);
	
		$title = self::remove_accents($title);
		if (self::seems_utf8($title)) {
			if (function_exists('mb_strtolower')) {
				$title = mb_strtolower($title, 'UTF-8');
			}
			$title = self::utf8_uri_encode($title, 200);
		}
	
		$title = strtolower($title);
		$title = preg_replace('/&.+?;/', '', $title); // kill entities
		$title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
		$title = preg_replace('/\s+/', '-', $title);
		$title = preg_replace('|-+|', '-', $title);
		$title = trim($title, '-');
	
		return $title;
	}
	
	public static function convert_chars($content, $flag = 'obsolete') {
		// Translation of invalid Unicode references range to valid range
		$htmltranswinuni = array(
		'&#128;' => '&#8364;', // the Euro sign
		'&#129;' => '',
		'&#130;' => '&#8218;', // these are Windows CP1252 specific characters
		'&#131;' => '&#402;',  // they would look weird on non-Windows browsers
		'&#132;' => '&#8222;',
		'&#133;' => '&#8230;',
		'&#134;' => '&#8224;',
		'&#135;' => '&#8225;',
		'&#136;' => '&#710;',
		'&#137;' => '&#8240;',
		'&#138;' => '&#352;',
		'&#139;' => '&#8249;',
		'&#140;' => '&#338;',
		'&#141;' => '',
		'&#142;' => '&#382;',
		'&#143;' => '',
		'&#144;' => '',
		'&#145;' => '&#8216;',
		'&#146;' => '&#8217;',
		'&#147;' => '&#8220;',
		'&#148;' => '&#8221;',
		'&#149;' => '&#8226;',
		'&#150;' => '&#8211;',
		'&#151;' => '&#8212;',
		'&#152;' => '&#732;',
		'&#153;' => '&#8482;',
		'&#154;' => '&#353;',
		'&#155;' => '&#8250;',
		'&#156;' => '&#339;',
		'&#157;' => '',
		'&#158;' => '',
		'&#159;' => '&#376;'
		);
	
		// Remove metadata tags
		$content = preg_replace('/<title>(.+?)<\/title>/','',$content);
		$content = preg_replace('/<category>(.+?)<\/category>/','',$content);
	
		// Converts lone & characters into &#38; (a.k.a. &amp;)
		$content = preg_replace('/&([^#])(?![a-z1-4]{1,8};)/i', '&#038;$1', $content);
	
		// Fix Word pasting
		$content = strtr($content, $htmltranswinuni);
	
		// Just a little XHTML help
		$content = str_replace('<br>', '<br />', $content);
		$content = str_replace('<hr>', '<hr />', $content);
	
		return $content;
	}
	
	/*
	 force_balance_tags
	
	 Balances Tags of string using a modified stack.
	
	 @param text      Text to be balanced
	 @param force     Forces balancing, ignoring the value of the option
	 @return          Returns balanced text
	 @author          Leonard Lin (leonard@acm.org)
	 @version         v1.1
	 @date            November 4, 2001
	 @license         GPL v2.0
	 @notes
	 @changelog
	 ---  Modified by Scott Reilly (coffee2code) 02 Aug 2004
		1.2  ***TODO*** Make better - change loop condition to $text
		1.1  Fixed handling of append/stack pop order of end text
			 Added Cleaning Hooks
		1.0  First Version
	*/
	public static function balance_tags( $text ) {
		$tagstack = array(); $stacksize = 0; $tagqueue = ''; $newtext = '';
		$single_tags = array('br', 'hr', 'img', 'input'); //Known single-entity/self-closing tags
		$nestable_tags = array('blockquote', 'div', 'span'); //Tags that can be immediately nested within themselves
	
		# WP bug fix for comments - in case you REALLY meant to type '< !--'
		$text = str_replace('< !--', '<    !--', $text);
		# WP bug fix for LOVE <3 (and other situations with '<' before a number)
		$text = preg_replace('#<([0-9]{1})#', '&lt;$1', $text);
	
		while (preg_match("/<(\/?\w*)\s*([^>]*)>/",$text,$regex)) {
			$newtext .= $tagqueue;
	
			$i = strpos($text,$regex[0]);
			$l = strlen($regex[0]);
	
			// clear the shifter
			$tagqueue = '';
			// Pop or Push
			if ($regex[1][0] == "/") { // End Tag
				$tag = strtolower(substr($regex[1],1));
				// if too many closing tags
				if($stacksize <= 0) {
					$tag = '';
					//or close to be safe $tag = '/' . $tag;
				}
				// if stacktop value = tag close value then pop
				else if ($tagstack[$stacksize - 1] == $tag) { // found closing tag
					$tag = '</' . $tag . '>'; // Close Tag
					// Pop
					array_pop ($tagstack);
					$stacksize--;
				} else { // closing tag not at top, search for it
					for ($j=$stacksize-1;$j>=0;$j--) {
						if ($tagstack[$j] == $tag) {
						// add tag to tagqueue
							for ($k=$stacksize-1;$k>=$j;$k--){
								$tagqueue .= '</' . array_pop ($tagstack) . '>';
								$stacksize--;
							}
							break;
						}
					}
					$tag = '';
				}
			} else { // Begin Tag
				$tag = strtolower($regex[1]);
	
				// Tag Cleaning
	
				// If self-closing or '', don't do anything.
				if((substr($regex[2],-1) == '/') || ($tag == '')) {
				}
				// ElseIf it's a known single-entity tag but it doesn't close itself, do so
				elseif ( in_array($tag, $single_tags) ) {
					$regex[2] .= '/';
				} else {	// Push the tag onto the stack
					// If the top of the stack is the same as the tag we want to push, close previous tag
					if (($stacksize > 0) && !in_array($tag, $nestable_tags) && ($tagstack[$stacksize - 1] == $tag)) {
						$tagqueue = '</' . array_pop ($tagstack) . '>';
						$stacksize--;
					}
					$stacksize = array_push ($tagstack, $tag);
				}
	
				// Attributes
				$attributes = $regex[2];
				if($attributes) {
					$attributes = ' '.$attributes;
				}
				$tag = '<'.$tag.$attributes.'>';
				//If already queuing a close tag, then put this tag on, too
				if ($tagqueue) {
					$tagqueue .= $tag;
					$tag = '';
				}
			}
			$newtext .= substr($text,0,$i) . $tag;
			$text = substr($text,$i+$l);
		}
	
		// Clear Tag Queue
		$newtext .= $tagqueue;
	
		// Add Remaining text
		$newtext .= $text;
	
		// Empty Stack
		while($x = array_pop($tagstack)) {
			$newtext .= '</' . $x . '>'; // Add remaining tags to close
		}
	
		// WP fix for the bug with HTML comments
		$newtext = str_replace("< !--","<!--",$newtext);
		$newtext = str_replace("<    !--","< !--",$newtext);
	
		return $newtext;
	}
	
	public static function format_to_edit($content, $richedit = false) {
		if (! $richedit )
			$content = htmlspecialchars($content);
		return $content;
	}
	
	public static function zeroise($number,$threshold) {
	  // function to add leading zeros when necessary
		return sprintf('%0'.$threshold.'s', $number);
	}
	
	public static function backslashit($string) {
		$string = preg_replace('/^([0-9])/', '\\\\\\\\\1', $string);
		$string = preg_replace('/([a-z])/i', '\\\\\1', $string);
		return $string;
	}
	
	public static function trailingslashit($string) {
		return self::untrailingslashit($string) . '/';
	}
	
	public static function untrailingslashit($string) {
		return rtrim($string, '/');
	}
	
	public static function stripslashes_deep($value) {
		 $value = is_array($value) ?
			 array_map('stripslashes_deep', $value) :
			 stripslashes($value);
	
		 return $value;
	}
	
	public static function urlencode_deep($value) {
		 $value = is_array($value) ?
			 array_map('urlencode_deep', $value) :
			 urlencode($value);
	
		 return $value;
	}
	
	public static function antispambot($emailaddy, $mailto=0) {
		$emailNOSPAMaddy = '';
		srand ((float) microtime() * 1000000);
		for ($i = 0; $i < strlen($emailaddy); $i = $i + 1) {
			$j = floor(rand(0, 1+$mailto));
			if ($j==0) {
				$emailNOSPAMaddy .= '&#'.ord(substr($emailaddy,$i,1)).';';
			} elseif ($j==1) {
				$emailNOSPAMaddy .= substr($emailaddy,$i,1);
			} elseif ($j==2) {
				$emailNOSPAMaddy .= '%'.self::zeroise(dechex(ord(substr($emailaddy, $i, 1))), 2);
			}
		}
		$emailNOSPAMaddy = str_replace('@','&#64;',$emailNOSPAMaddy);
		return $emailNOSPAMaddy;
	}
	
	public static function make_clickable($ret) {
		$ret = ' ' . $ret;
		// in testing, using arrays here was found to be faster
		$ret = preg_replace(
			array(
				'#([\s>])([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*)#is',
				'#([\s>])((www|ftp)\.[\w\#$%&~/.\-;:=,?@\[\]+]*)#is',
				'#([\s>])([a-z0-9\-_.]+)@([^,< \n\r]+)#i'),
			array(
				'$1<a href="$2" rel="nofollow">$2</a>',
				'$1<a href="http://$2" rel="nofollow">$2</a>',
				'$1<a href="mailto:$2@$3">$2@$3</a>'),$ret);
		// this one is not in an array because we need it to run last, for cleanup of accidental links within links
		$ret = preg_replace("#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i", "$1$3</a>", $ret);
		$ret = trim($ret);
		return $ret;
	}
	
	public static function rel_nofollow( $text ) {
		// This is a pre save filter, so text is already escaped.
		$text = stripslashes($text);
		$text = preg_replace('|<a (.+?)>|ie', "'<a ' . str_replace(' rel=\"nofollow\"','',stripslashes('$1')) . ' rel=\"nofollow\">'", $text);
		return $text;
	}
	
	public static function is_email($user_email) {
		$chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
		if (strpos($user_email, '@') !== false && strpos($user_email, '.') !== false) {
			if (preg_match($chars, $user_email)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	public static function iso_descrambler($string) {
	  /* this may only work with iso-8859-1, I'm afraid */
	  if (!preg_match('#\=\?(.+)\?Q\?(.+)\?\=#i', $string, $matches)) {
		return $string;
	  } else {
		$subject = str_replace('_', ' ', $matches[2]);
		$subject = preg_replace('#\=([0-9a-f]{2})#ei', "chr(hexdec(strtolower('$1')))", $subject);
		return $subject;
	  }
	}
	
	// give it a date, it will give you the same date as GMT
	public static function get_gmt_from_date($string) {
	  // note: this only substracts $time_difference from the given date
	  preg_match('#([0-9]{1,4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})#', $string, $matches);
	  $string_time = gmmktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
	  $string_gmt = gmdate('Y-m-d H:i:s', $string_time - get_option('gmt_offset') * 3600);
	  return $string_gmt;
	}
	
	// give it a GMT date, it will give you the same date with $time_difference added
	public static function get_date_from_gmt($string) {
	  // note: this only adds $time_difference to the given date
	  preg_match('#([0-9]{1,4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})#', $string, $matches);
	  $string_time = gmmktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
	  $string_localtime = gmdate('Y-m-d H:i:s', $string_time + get_option('gmt_offset')*3600);
	  return $string_localtime;
	}
	
	// computes an offset in seconds from an iso8601 timezone
	public static function iso8601_timezone_to_offset($timezone) {
	  // $timezone is either 'Z' or '[+|-]hhmm'
	  if ($timezone == 'Z') {
		$offset = 0;
	  } else {
		$sign    = (substr($timezone, 0, 1) == '+') ? 1 : -1;
		$hours   = intval(substr($timezone, 1, 2));
		$minutes = intval(substr($timezone, 3, 4)) / 60;
		$offset  = $sign * 3600 * ($hours + $minutes);
	  }
	  return $offset;
	}
	
	public static function popup_links($text) {
		// Comment text in popup windows should be filtered through this.
		// Right now it's a moderately dumb function, ideally it would detect whether
		// a target or rel attribute was already there and adjust its actions accordingly.
		$text = preg_replace('/<a (.+?)>/i', "<a $1 target='_blank' rel='external'>", $text);
		return $text;
	}
	
	public static function sanitize_email($email) {
		return preg_replace('/[^a-z0-9+_.@-]/i', '', $email);
	}
	
	public static function trim_excerpt($text) { // Fakes an excerpt if needed
		if ( '' == $text ) {
			$text = str_replace(']]>', ']]&gt;', $text);
			$text = strip_tags($text);
			$excerpt_length = 55;
			$words = explode(' ', $text, $excerpt_length + 1);
			if (count($words) > $excerpt_length) {
				array_pop($words);
				array_push($words, '[...]');
				$text = implode(' ', $words);
			}
		}
		return $text;
	}
	
	public static function ent2ncr($text) {
		$to_ncr = array(
			'&quot;' => '&#34;',
			'&amp;' => '&#38;',
			'&frasl;' => '&#47;',
			'&lt;' => '&#60;',
			'&gt;' => '&#62;',
			'|' => '&#124;',
			'&nbsp;' => '&#160;',
			'&iexcl;' => '&#161;',
			'&cent;' => '&#162;',
			'&pound;' => '&#163;',
			'&curren;' => '&#164;',
			'&yen;' => '&#165;',
			'&brvbar;' => '&#166;',
			'&brkbar;' => '&#166;',
			'&sect;' => '&#167;',
			'&uml;' => '&#168;',
			'&die;' => '&#168;',
			'&copy;' => '&#169;',
			'&ordf;' => '&#170;',
			'&laquo;' => '&#171;',
			'&not;' => '&#172;',
			'&shy;' => '&#173;',
			'&reg;' => '&#174;',
			'&macr;' => '&#175;',
			'&hibar;' => '&#175;',
			'&deg;' => '&#176;',
			'&plusmn;' => '&#177;',
			'&sup2;' => '&#178;',
			'&sup3;' => '&#179;',
			'&acute;' => '&#180;',
			'&micro;' => '&#181;',
			'&para;' => '&#182;',
			'&middot;' => '&#183;',
			'&cedil;' => '&#184;',
			'&sup1;' => '&#185;',
			'&ordm;' => '&#186;',
			'&raquo;' => '&#187;',
			'&frac14;' => '&#188;',
			'&frac12;' => '&#189;',
			'&frac34;' => '&#190;',
			'&iquest;' => '&#191;',
			'&Agrave;' => '&#192;',
			'&Aacute;' => '&#193;',
			'&Acirc;' => '&#194;',
			'&Atilde;' => '&#195;',
			'&Auml;' => '&#196;',
			'&Aring;' => '&#197;',
			'&AElig;' => '&#198;',
			'&Ccedil;' => '&#199;',
			'&Egrave;' => '&#200;',
			'&Eacute;' => '&#201;',
			'&Ecirc;' => '&#202;',
			'&Euml;' => '&#203;',
			'&Igrave;' => '&#204;',
			'&Iacute;' => '&#205;',
			'&Icirc;' => '&#206;',
			'&Iuml;' => '&#207;',
			'&ETH;' => '&#208;',
			'&Ntilde;' => '&#209;',
			'&Ograve;' => '&#210;',
			'&Oacute;' => '&#211;',
			'&Ocirc;' => '&#212;',
			'&Otilde;' => '&#213;',
			'&Ouml;' => '&#214;',
			'&times;' => '&#215;',
			'&Oslash;' => '&#216;',
			'&Ugrave;' => '&#217;',
			'&Uacute;' => '&#218;',
			'&Ucirc;' => '&#219;',
			'&Uuml;' => '&#220;',
			'&Yacute;' => '&#221;',
			'&THORN;' => '&#222;',
			'&szlig;' => '&#223;',
			'&agrave;' => '&#224;',
			'&aacute;' => '&#225;',
			'&acirc;' => '&#226;',
			'&atilde;' => '&#227;',
			'&auml;' => '&#228;',
			'&aring;' => '&#229;',
			'&aelig;' => '&#230;',
			'&ccedil;' => '&#231;',
			'&egrave;' => '&#232;',
			'&eacute;' => '&#233;',
			'&ecirc;' => '&#234;',
			'&euml;' => '&#235;',
			'&igrave;' => '&#236;',
			'&iacute;' => '&#237;',
			'&icirc;' => '&#238;',
			'&iuml;' => '&#239;',
			'&eth;' => '&#240;',
			'&ntilde;' => '&#241;',
			'&ograve;' => '&#242;',
			'&oacute;' => '&#243;',
			'&ocirc;' => '&#244;',
			'&otilde;' => '&#245;',
			'&ouml;' => '&#246;',
			'&divide;' => '&#247;',
			'&oslash;' => '&#248;',
			'&ugrave;' => '&#249;',
			'&uacute;' => '&#250;',
			'&ucirc;' => '&#251;',
			'&uuml;' => '&#252;',
			'&yacute;' => '&#253;',
			'&thorn;' => '&#254;',
			'&yuml;' => '&#255;',
			'&OElig;' => '&#338;',
			'&oelig;' => '&#339;',
			'&Scaron;' => '&#352;',
			'&scaron;' => '&#353;',
			'&Yuml;' => '&#376;',
			'&fnof;' => '&#402;',
			'&circ;' => '&#710;',
			'&tilde;' => '&#732;',
			'&Alpha;' => '&#913;',
			'&Beta;' => '&#914;',
			'&Gamma;' => '&#915;',
			'&Delta;' => '&#916;',
			'&Epsilon;' => '&#917;',
			'&Zeta;' => '&#918;',
			'&Eta;' => '&#919;',
			'&Theta;' => '&#920;',
			'&Iota;' => '&#921;',
			'&Kappa;' => '&#922;',
			'&Lambda;' => '&#923;',
			'&Mu;' => '&#924;',
			'&Nu;' => '&#925;',
			'&Xi;' => '&#926;',
			'&Omicron;' => '&#927;',
			'&Pi;' => '&#928;',
			'&Rho;' => '&#929;',
			'&Sigma;' => '&#931;',
			'&Tau;' => '&#932;',
			'&Upsilon;' => '&#933;',
			'&Phi;' => '&#934;',
			'&Chi;' => '&#935;',
			'&Psi;' => '&#936;',
			'&Omega;' => '&#937;',
			'&alpha;' => '&#945;',
			'&beta;' => '&#946;',
			'&gamma;' => '&#947;',
			'&delta;' => '&#948;',
			'&epsilon;' => '&#949;',
			'&zeta;' => '&#950;',
			'&eta;' => '&#951;',
			'&theta;' => '&#952;',
			'&iota;' => '&#953;',
			'&kappa;' => '&#954;',
			'&lambda;' => '&#955;',
			'&mu;' => '&#956;',
			'&nu;' => '&#957;',
			'&xi;' => '&#958;',
			'&omicron;' => '&#959;',
			'&pi;' => '&#960;',
			'&rho;' => '&#961;',
			'&sigmaf;' => '&#962;',
			'&sigma;' => '&#963;',
			'&tau;' => '&#964;',
			'&upsilon;' => '&#965;',
			'&phi;' => '&#966;',
			'&chi;' => '&#967;',
			'&psi;' => '&#968;',
			'&omega;' => '&#969;',
			'&thetasym;' => '&#977;',
			'&upsih;' => '&#978;',
			'&piv;' => '&#982;',
			'&ensp;' => '&#8194;',
			'&emsp;' => '&#8195;',
			'&thinsp;' => '&#8201;',
			'&zwnj;' => '&#8204;',
			'&zwj;' => '&#8205;',
			'&lrm;' => '&#8206;',
			'&rlm;' => '&#8207;',
			'&ndash;' => '&#8211;',
			'&mdash;' => '&#8212;',
			'&lsquo;' => '&#8216;',
			'&rsquo;' => '&#8217;',
			'&sbquo;' => '&#8218;',
			'&ldquo;' => '&#8220;',
			'&rdquo;' => '&#8221;',
			'&bdquo;' => '&#8222;',
			'&dagger;' => '&#8224;',
			'&Dagger;' => '&#8225;',
			'&bull;' => '&#8226;',
			'&hellip;' => '&#8230;',
			'&permil;' => '&#8240;',
			'&prime;' => '&#8242;',
			'&Prime;' => '&#8243;',
			'&lsaquo;' => '&#8249;',
			'&rsaquo;' => '&#8250;',
			'&oline;' => '&#8254;',
			'&frasl;' => '&#8260;',
			'&euro;' => '&#8364;',
			'&image;' => '&#8465;',
			'&weierp;' => '&#8472;',
			'&real;' => '&#8476;',
			'&trade;' => '&#8482;',
			'&alefsym;' => '&#8501;',
			'&crarr;' => '&#8629;',
			'&lArr;' => '&#8656;',
			'&uArr;' => '&#8657;',
			'&rArr;' => '&#8658;',
			'&dArr;' => '&#8659;',
			'&hArr;' => '&#8660;',
			'&forall;' => '&#8704;',
			'&part;' => '&#8706;',
			'&exist;' => '&#8707;',
			'&empty;' => '&#8709;',
			'&nabla;' => '&#8711;',
			'&isin;' => '&#8712;',
			'&notin;' => '&#8713;',
			'&ni;' => '&#8715;',
			'&prod;' => '&#8719;',
			'&sum;' => '&#8721;',
			'&minus;' => '&#8722;',
			'&lowast;' => '&#8727;',
			'&radic;' => '&#8730;',
			'&prop;' => '&#8733;',
			'&infin;' => '&#8734;',
			'&ang;' => '&#8736;',
			'&and;' => '&#8743;',
			'&or;' => '&#8744;',
			'&cap;' => '&#8745;',
			'&cup;' => '&#8746;',
			'&int;' => '&#8747;',
			'&there4;' => '&#8756;',
			'&sim;' => '&#8764;',
			'&cong;' => '&#8773;',
			'&asymp;' => '&#8776;',
			'&ne;' => '&#8800;',
			'&equiv;' => '&#8801;',
			'&le;' => '&#8804;',
			'&ge;' => '&#8805;',
			'&sub;' => '&#8834;',
			'&sup;' => '&#8835;',
			'&nsub;' => '&#8836;',
			'&sube;' => '&#8838;',
			'&supe;' => '&#8839;',
			'&oplus;' => '&#8853;',
			'&otimes;' => '&#8855;',
			'&perp;' => '&#8869;',
			'&sdot;' => '&#8901;',
			'&lceil;' => '&#8968;',
			'&rceil;' => '&#8969;',
			'&lfloor;' => '&#8970;',
			'&rfloor;' => '&#8971;',
			'&lang;' => '&#9001;',
			'&rang;' => '&#9002;',
			'&larr;' => '&#8592;',
			'&uarr;' => '&#8593;',
			'&rarr;' => '&#8594;',
			'&darr;' => '&#8595;',
			'&harr;' => '&#8596;',
			'&loz;' => '&#9674;',
			'&spades;' => '&#9824;',
			'&clubs;' => '&#9827;',
			'&hearts;' => '&#9829;',
			'&diams;' => '&#9830;'
		);
	
		return str_replace( array_keys($to_ncr), array_values($to_ncr), $text );
	}
	
	public static function richedit_pre($text){
		// Filtering a blank results in an annoying <br />\n
		if ( empty($text) ) return $text;
	
		$output = $text;
		$output = self::convert_chars($output);
		$output = self::autop($output);
	
		// These must be double-escaped or planets will collide.
		$output = str_replace('&lt;', '&amp;lt;', $output);
		$output = str_replace('&gt;', '&amp;gt;', $output);
	
		return $output;
	}
	
	public static function clean_url($url, $protocols = null){
		if ('' == $url) return $url;
		$url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%]|i', '', $url);
		$strip = array('%0d', '%0a');
		$url = str_replace($strip, '', $url);
		$url = str_replace(';//', '://', $url);
		// Append http unless a relative link starting with / or a php file.
		if ( strpos($url, '://') === false &&
			substr( $url, 0, 1 ) != '/' && !preg_match('/^[a-z0-9-]+?\.php/i', $url) )
			$url = 'http://' . $url;
		
		$url = preg_replace('/&([^#])(?![a-z]{2,8};)/', '&#038;$1', $url);
		if ( !is_array($protocols) )
			$protocols = array('http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet'); 
		return $url;
	}
	
	// Borrowed from the PHP Manual user notes. Convert entities, while
	// preserving already-encoded entities:
	public static function htmlentities($myHTML) {
		$translation_table=get_html_translation_table (HTML_ENTITIES,ENT_QUOTES);
		$translation_table[chr(38)] = '&';
		return preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/","&amp;" , strtr($myHTML, $translation_table));
	}
	
	// Escape single quotes, specialchar double quotes, and fix line endings.
	public static function js_escape($text) {
		$safe_text = self::specialchars($text, 'double');
		$safe_text = preg_replace('/&#(x)?0*(?(1)27|39);?/i', "'", stripslashes($safe_text));
		$safe_text = preg_replace("/\r?\n/", "\\n", addslashes($safe_text));
		return $safe_text;
	}
	
	// Escaping for HTML attributes
	
	public static function make_link_relative( $link ) {
		return preg_replace('|https?://[^/]+(/.*)|i', '$1', $link );
	}

	// DOGSTER FUNCS
	public static function smart_trim($text, $max_len=50, $trim_middle = false, $trim_chars = '&hellip;'){
		$text = trim($text);
		if(strlen($text) < $max_len){
			return $text;
		}elseif($trim_middle){
			$hasSpace = strpos($text, ' ');
			if(!$hasSpace){
				$first_half = substr($text, 0, $max_len / 2);
				$last_half = substr($text, -($max_len - strlen($first_half)));
			}else{
				$last_half = substr($text, -($max_len / 2));
				$last_half = trim($last_half);
				$last_space = strrpos($last_half, ' ');
				if(!($last_space === false)){
					$last_half = substr($last_half, $last_space + 1);
				}
				$first_half = substr($text, 0, $max_len - strlen($last_half));
				$first_half = trim($first_half);
				if(substr($text, $max_len - strlen($last_half), 1) == ' '){
					$first_space = $max_len - strlen($last_half);
				}else{
					$first_space = strrpos($first_half, ' ');
				}
				if(!($first_space === false)){
					$first_half = substr($text, 0, $first_space);
				}
			}
			return $first_half.$trim_chars.$last_half;
		}else{
			$trimmed_text = substr($text, 0, $max_len);
			$trimmed_text = trim($trimmed_text);
			if(substr($text, $max_len, 1) == ' '){
				$last_space = $max_len;
			}else{
				$last_space = strrpos($trimmed_text, ' ');
			}
			if(!($last_space === false)){
				$trimmed_text = substr($trimmed_text, 0, $last_space);
			}
			return self::remove_trailing_punctuation($trimmed_text).$trim_chars;
		}
	}
	
	public static function remove_trailing_punctuation($text){
		return preg_replace("'[^a-zA-Z_0-9\>]+$'s", '', $text);
	}
	
	public static function convert_spaces($text){
		return str_replace(" ", "%20", $text);
	}
	
	public static function htmlencode($string){
		return htmlentities($string, ENT_QUOTES, 'utf-8');
	}
	
	public static function summary($string,$hilight=NULL,$length=100){
		//$textile = new Textile;
		//$string = $textile->TextileThis($string);	
		$string = strip_tags($string);
		if($hilight){
			/*$hilight = "/(".str_replace("+","+)(",urlencode($hilight))."+)/i";
			$replacement = '<span class=\"match\">$1</span>';
			$string = preg_replace($hilight, $replacement, $string);*/
			$hilight = explode("+",urlencode($hilight));
			if(is_array($hilight)){
				foreach($hilight as $val){
					$pos = strpos($string, $val);
					$string = str_replace($val, "<span class=\"match\">$val</span>", $string);
				};
			}else{
				$pos = strpos($string, $hilight);
				$string = str_replace($hilight, "<span class=\"match\">$hilight</span>", $string);
			};
			$string = self::smart_trim($string, $length);
		}else{
			$string = self::smart_trim($string, $length);
		}
		return $string;
	}
	
	public static function paragrapher($string,$length=NULL){
		$string = $length? self::smart_trim($string, $length) : $string; // trim if needed
		$string = stripslashes($string); // add line breaks
		$string = htmlspecialchars($string); // add line breaks
		$string = self::autoLink($string); // add url a tags
		$string = nl2br($string); // add line breaks
		return $string;
	}
	
	public static function auto_link($string){
		return preg_replace('/(http|ftp)+(s)?:(\/\/)((\w|\.)+)(\/)?(\S+)?/i','<a href="\0">\4</a>',$string);
	}
	
	public static function clean_text_for_url($text, $length=100){
		return substr(low(self::clean_text_for_url(stripslashes($text))),0,$length);
		//return substr(preg_replace('/[^a-z_]/','',preg_replace('/[ ]+/','_',strtolower(stripslashes($text)))),0,$length);
		//return substr(strtolower(sanitize($text)),0,$length);
	}
	
	public static function slugify($text, $length=100){
		return self::clean_text_for_url($text, $length);
	}
	
	public static function parse_tags($tags,$pre=''){
		$tags = explode(',',$tags);
		$links = '<span class="tags">';
		foreach($tags as $tag){
			if(!empty($tag)){
				$tag = trim(str_replace(array('.','\'',"\n","\t","\r"),'',$tag));
				$links .= "<a href=\"$pre/".PROJECT_NAME."/tag/$tag\" rel=\"tag\">$tag</a>, ";
			}
		}
		$links = self::remove_trailing_punctuation($links);
		$links .= '</span>';
		return $links;
	}

	public static function format_question($text, $length=false, $end = "?"){
		$text = ucfirst(trim($text));
		
		$match = '/(\.{2})$/i';
		$replace = '&hellip;';
		if(preg_match($match, $text)){
			return preg_replace($match, $replace, $text);
		}
		
		$match = '/([\?\!\.\:\;\-\_\&\@\#\$\%\*\^\,\/\\'.$end.']+)$/i';
		$replace = $end;
		if(preg_match($match, $text)){
			return preg_replace($match, $replace, $text);
		}
		
		return $text.$end;
	}
	
	public static function a_or_an($word) {
    if (preg_match("/[aeiou]/i", substr($word, 0, 1))){
			return "an";
    }
    return 'a';
	}
	
	public static function pluralize($word) {
		$plural_rules = array(
			'/series$/'               => '\1series',
			'/([^aeiouy]|qu)ies$/'   => '\1y',
			'/([^aeiouy]|qu)y$/'      => '\1ies',      # query, ability, agency
			'/(?:([^f])fe|([lr])f)$/' => '\1\2ves', # half, safe, wife
			'/sis$/'                  => 'ses',        # basis, diagnosis
			'/([ti])um$/'            => '\1a',        # datum, medium
			'/person$/'               => 'people',     # person, salesperson
			'/^man$/'                  => 'men',       # man
			'/woman$/'                  => 'women',       # woman
			'/child$/'               => 'children',   # child
			'/s$/'                  => 's',          # no change (compatibility)
			'/$/'                     => 's'
			);
	
		foreach ($plural_rules as $rule => $replacement) {
			if (preg_match($rule, $word)) {
				return preg_replace($rule, $replacement, $word);
			}
		}
		return false;
	}
	
	public static function title_case($str){
		// Set the words that shouldn't be capitalized
		$small_words = array('a', 'an', 'and', 'as', 'at', 'but', 'by', 'en', 'for', 'if', 'in', 'of', 'on', 'or', 'the', 'to', 'v[.]?', 'via', 'vs[.]?');
		$small_re = '^' . implode("$|^", $small_words) . '$';
		
		// Set patterns to convert quote html entities from string
		$patterns[0] = '/&#8216;|&#8217;/';
		$patterns[1] = '/&#8220;|&#8221;/';
		$replacements[0] = '\'';
		$replacements[1] = '"';
		
		// Remove html character entities from string
		$new_str = preg_replace($patterns, $replacements, $str);
	
		// Split the string by words so we can process it
		$chars = preg_split('/( [:.;?!"î\'í][ ][\'ë"ì]? | (?:[ ]|^)[\'ë"ì] | [[:space:]] )/x', $new_str, -1, PREG_SPLIT_DELIM_CAPTURE);
		$chars_num = count($chars);
	
		$line = "";
		// find out which item in the array holds the first word
		if (!$chars[0]):
			$first_word = 2;
		else:
			$first_word = 0;
		endif;
		
		for ($num = 0; $num < $chars_num; $num += 1) {
			$word = $chars[$num];
		
			// Skip words with characters other than the first letter already capitalized
			if (preg_match('/( [a-z]+ [A-Z]+ )/x', $word)):
				$newword = $word;
			// Skip words with inline dots, e.g. "del.icio.us" or "example.com"
			elseif (preg_match('/( [[:alpha:]] [.] [[:alpha:]] )/x', $word)):
				$newword = $word;
			// Lowercase our list of small words as long as it isn't the first or last word
			elseif (preg_match('/('.$small_re.')/i', $word) AND $num != $first_word AND $num != $chars_num-1):
				$newword = strtolower($word);
			else:
				$newword = ucfirst($word);
			endif;
	
			$line .= $newword;
		};
		
		// Put the html entities back in the string
		// $new_line = preg_replace($revpatterns, $revreplacements, $line);
	
	    return $line;
	}
	
  /**
   * This thing makes a descriptive sentence out of an array of strings.
   * If it's empty, it will return an empty string.
   *
   * @param array $attributes the adjectives
   * @param string $start something to start the sentence with
   * @param string $ending goes before the last word
   * @param string $comma a comma (replace with "and"?)
   * @return string
   */
  public static function describer(array $attributes = null, $start = 'is', $ending = 'and is', $comma = ','){
  	$cnt=0;
  	if(join('',$attributes)==''){
  		return false;
  	}
  	$sentence = count($attributes)==0 ? '' : ' '.$start.' ';
  	foreach($attributes as $word){
  		if(trim($word)==''){
  			continue;
  		}
  		$cnt+=1;
  		if($cnt==count($attributes)){
  			$sentence .= $word; // the last word
  		}elseif($cnt==count($attributes)-1){
  			$sentence .= $word.' '.$ending.' '; // right before the last word
  		}else{
  			$sentence .= $word.$comma.' '; // all the other words
  		}
  	}
  	return $sentence.'.';
  }
  
  // Copied from here:
  // http://www.geosourcecode.com/post380.html
  public static function encrypt($sData){
  	return urlencode(base64_encode($sData));
  }

  // Copied from here:
  // http://www.geosourcecode.com/post380.html
  public static function decrypt($sData){
  	return base64_decode(urldecode($sData));
  }
  
  /**
   *
   *this code borrowed from http://milianw.de/section:Snippets/content:Close-HTML-Tags
   *note: it closes open tags, but does not handle cases of incomplete OPEN tags. for example, an tag that is cut off before the closing quote of an attribute will not be closed properly with a close quote and greater than (i.e. '<img src="http:// ')
   *So this only works if tags are in OPENING tags are in perfect shape.
   *tedr.
  */
  public static function close_tags($html){

  	#put all opened tags into an array
  	preg_match_all("#<([a-z]+)( .*)?(?!/)>#iU",$html,$result);
  	$openedtags=$result[1];

  	#put all closed tags into an array
  	preg_match_all("#</([a-z]+)>#iU",$html,$result);
  	$closedtags=$result[1];
  	$len_opened = count($openedtags);

  	#all tags are closed
  	if(count($closedtags) == $len_opened){
  		return $html;
  	}
  	$openedtags = array_reverse($openedtags);

  	#close tags
  	for($i=0;$i < $len_opened;$i++) {
  		if (!in_array($openedtags[$i],$closedtags)){
  			$html .= '</'.$openedtags[$i].'>';
  		} else {
  			unset($closedtags[array_search($openedtags[$i],$closedtags)]);
  		}
  	}
  	return $html;
  }
  
  /**
   * This just keeps the html tags that we're generally okay with
   * @param $html
   * @return html
   */
  public static function ok_tags($html){
  	return self::closetags(strip_tags($html, "<b><strong><i><em>"));
  }
}
