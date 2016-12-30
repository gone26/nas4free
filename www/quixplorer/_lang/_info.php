<?php
/*
	_info.php
	
	Part of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2017 The NAS4Free Project <info@nas4free.org>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice, this
	   list of conditions and the following disclaimer.
	2. Redistributions in binary form must reproduce the above copyright notice,
	   this list of conditions and the following disclaimer in the documentation
	   and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
	ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

	The views and conclusions contained in the software and documentation are those
	of the authors and should not be interpreted as representing official policies,
	either expressed or implied, of the NAS4Free Project.
*/
	//Set the language array
	$lang = array(
		'en_US'	=> 'English',
		'sq'		=> 'Shqip',
		'bg'		=> 'Български',
		'cs'		=> 'čeština',
		'da'		=> 'Dansk',
		'de'		=> 'Deutsch',
		'es'		=> 'Español',
		'fi'		=> 'Suomi',
		'fr'		=> 'Français',
		'el'		=> 'Ελληνικά',
		'hu'		=> 'Magyar',
		'it'		=> 'Italiano',
		'ja'		=> '日本語',
		'ko'		=> '한국어',
		'lv'		=> 'Latviešu',
		'nl'		=> 'Nederlands',
		'nb'		=> 'Norsk (bokmål)',
		'pl'		=> 'Polski',
		'pt'		=> 'Português',
		'pt_BR'		=> 'Português - Brasil',
		'ro'		=> 'Română',
		'ru'		=> 'Русский',
		'sk'		=> 'Slovenský',
		'sl'		=> 'Slovenščina',
		'sv'		=> 'Svenska',
		'tr'		=> 'Türkçe',
		'uk'		=> 'Українська',
		'zh_CN'	=> '中文（簡體）',
		'zh_TW'	=> '正體中文'
);

	//Create the select box and options
	echo "<SELECT name=\"lang\">\n";
		foreach($lang as $key => $value) {
			//Set the default language automatically based on global webgui language
			$selected = ($key == $GLOBALS["language"]) ? " selected='selected'" : '';
			//Now create the <options> list
			echo "<option value='$key'$selected>$value</option>\n";
}
	echo "</SELECT></TD></TR>\n";