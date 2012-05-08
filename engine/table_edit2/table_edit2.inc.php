<?php

// table_edit2.inc.php, 3.1+calc0.6+func0.3 2009/11/19 taru        plugin

define('PLUGIN_TABLE_EDIT2_TEXT_SIZE',  58);
define('PLUGIN_TABLE_EDIT2_TEXTAREA_ROWS_LINE',  4);		// textarea
define('PLUGIN_TABLE_EDIT2_TEXTAREA_COLS_SIZE', 40);
define('PLUGIN_TABLE_EDIT2_TEXTAREA',  'text');				// textarea
define('PLUGIN_TABLE_EDIT2_TD_SHOW', TRUE);			// td_edit_and_add	 TRUE or FALSE
define('PLUGIN_TABLE_EDIT2_TR_SHOW', TRUE);			// td_edit_and_add	 TRUE or FALSE
define('PLUGIN_TABLE_EDIT2_ADD_SHOW', 1);
if (!defined('IMAGE_URI')) define('IMAGE_URI', IMAGE_DIR);
define('PLUGIN_TABLE_EDIT2_CALC', 1);
define('PLUGIN_TABLE_EDIT2_HTTP_REFERER', 0);	//$_SERVER['HTTP_REFERER']
define('PLUGIN_TABLE_EDIT2_ANCHR_JUMP', 0);		//anchr end
define('PLUGIN_TABLE_EDIT2_MAX_FILESIZE', (512 * 1024)); // default: 0.5MB
define('PLUGIN_TABLE_EDIT2_CSV_UNLINK', TRUE);			//file deletion	 TRUE or FALSE

function plugin_table_edit2_convert()
{
	global $vars, $edit_auth, $edit_auth_pages;
	static $number = array();
	$line_count = 1;
	$table_end = 0;
	$table_sub_num = 1;
	$column_sum_or_avg = 0;
	$page = isset($vars['page']) ? $vars['page'] : '';
	if (! isset($number[$page])) $number[$page] = 1;
	$count = $number[$page]++;
	$r_page = rawurlencode($page);
	if (! function_exists('_')) table_edit2_message();

	$opt = array(
		'edit' => 'on',
		'dot' => '0',
		'td_edit' => 'on',
		'tr_edit' => 'on',
		'setting' => '',
		'csv_select' => '',
		'abs_m' => FALSE,
		'table_mod' => '',
	);

	if (! class_exists('auth')) { table_edit2_auth(); }
	$auth_chk = auth::check_auth();
	foreach($edit_auth_pages as $key=>$val){
		if (preg_match($key, $page) && $edit_auth){
			$opt['edit'] = 'off';
			if(! empty($auth_chk)){
				$opt['edit'] = 'on';
			}
		}
	}

	$head_button = '';
	$body = '';
	$body_table = '';

	$args = func_get_args();
	$arg = (substr(end($args), -1) == "\r")? array_pop($args) : '';
	foreach ($args as $opt_key) {
		list($key, $val) = explode('=', strtolower($opt_key));
		$opt[$key] = htmlspecialchars($val);
	}

	// if (PKWK_READONLY) {
	if (auth::check_role('readonly') || is_freeze($page)) {	// || is_freeze($page) 
		$opt['edit'] = 'off';
	}

	// plugin setting
	if ( ( !isset($arg) || $arg == '' || $opt['setting'] == 'on' ) && $opt['edit'] != 'off' && !isset($opt['csv'])) {
		$set = new TableEdit2Setting($page, $count);
		$set->table_data = (!isset($arg) || $arg == '') ? 0 : 1;
		return $set->form( $opt );
	}

	// csv
	if ( ( $opt['csv_select'] == 'import' || $opt['csv_select'] == 'export' || array_search('import', $args) !== FALSE ) && $opt['edit'] != 'off' ) {
		$csv = new TableEdit2Csv( htmlspecialchars($page), $count);
		return $csv->set_csv($opt['csv_select']);
	}

	// calc on off
	$opt['calc'] = isset($opt['calc']) ? PLUGIN_TABLE_EDIT2_CALC : 0;
	$calc_chk = "";
	if ($opt['calc'] === 1) {
		$calc = new TableEdit2Calc;
		$calc_chk = 'on';
	}

	//td edit
	if (PLUGIN_TABLE_EDIT2_TD_SHOW === TRUE) {
		$td_edit = ($opt['td_edit'] == 'off') ? 0 : 1;
		$td_edit_chk = ($opt['td_edit'] == 'on') ? 1 : 0;
	} else {
		$td_edit = 0;
	}
	//tr edit
	if (PLUGIN_TABLE_EDIT2_TR_SHOW === TRUE) {
		$tr_edit = ($opt['tr_edit'] == 'off') ? 0 : 1;
		$tr_edit_chk = ($opt['tr_edit'] == 'on') ? 1 : 0;
	} else {
		$tr_edit = 0;
	}

	if ($opt['edit'] != 'off') $tei = new TableEdit2Indicate($r_page, $count);		//open or close | inline

	// open or close
	if ($opt['table_mod'] != 'off' && $opt['edit'] != 'off') {
		if ( isset($opt['csv']) ) $tei->csv_button($opt['csv']);
		$head_button = $tei->open_close($opt['table_mod'],& $opt['edit']);
	}

	$arg = preg_replace(array("[\\r|\\n]","[\\r]"), array("\n","\n"), $arg);
	$args = explode("\n", $arg);

	if ( $opt['edit'] != 'off' || $calc_chk == 'on' || isset($opt['edit']) ) $editon_or_calcon = 1;		//06.09.19

	foreach ($args as $args_line) {
	
		$table_f_chose = 0;
		$table_f_chose = (preg_match('/^\|(.+)\|([hHfFcC]?)$/', $args_line, $matches)) ? 1 : 0;
		if ($args_line{0} === ',') $table_f_chose = 2;

		if ($table_f_chose && $editon_or_calcon){

			if ($table_f_chose === 1) {
				$match_cells = explode("|", $matches[1]);
			} elseif ($table_f_chose === 2) {
				$match_cells = csv_explode(',', substr($args_line, 1));
				$matches = array( 1 => join('|', $match_cells), 2 => '' );
			}
			
			if ($line_count === 1) $r_cell_count = count($match_cells);		//06.11.17 sort

			if ( $calc_chk == 'on' ){

				if (strtolower($matches[2]) == "c") $calc->cell_format = '';
				$calc->line_count  = $line_count;
				$calc->comma = isset($opt['comma']) ? ',' : '';
				$calc->dot   = isset($opt['dot']) ? $opt['dot'] : 0;
				$calc->c_format = isset($opt['format_c']) ?
					$calc->opt_c($opt['format_c'],$r_cell_count) : array_fill(0, $r_cell_count + 1, TRUE);
				$calc->m_abs = $opt['abs_m'];
				$calc->c_abs = isset($opt['abs_c']) ?
					$calc->opt_c($opt['abs_c'],$r_cell_count) : array_fill(0, $r_cell_count + 1, FALSE);
				$calc->row = $opt['row'];
				$calc->column = $opt['column'];

				$calc->calc($match_cells);

				$calc->calc_row();

				$cell_count_max = $calc->cell_count;
				$body_table .= $calc->body_table;
			} else {
				$body_table .= '|' . $matches[1] . '|';
			}
			if( $opt['edit'] != 'off' && $tr_edit ){		//edit
				if ($tr_edit_chk || $opt['tr_edit'] == 'edit') $body_table .= $tei->inline('show', $count, $line_count);
				if ($tr_edit_chk || $opt['tr_edit'] == 'add') $body_table .= $tei->inline('tr', $count, $line_count);
				$body_table .= '|';
			}
			$body_table .= $matches[2] . "\n";
			$line_count++;
			$table_end = 1;
			$table_header = 1;
		} elseif ( substr($args_line, 0, 2) != '//' ) {
			if( $table_header === 1 && $opt['edit'] != 'off' && $td_edit){		//td edit and add 06.09.16
				$cell_count = 1;
				$table_line_count = $line_count- 1;
				$body .= '|';
				foreach ($match_cells as $cell){
					$body .= '~';
					if ($td_edit_chk || $opt['td_edit'] == 'edit') $body .= $tei->inline('tdshow', $count, $table_line_count, $table_sub_num, $cell_count);
					if ($td_edit_chk || $opt['td_edit'] == 'add') $body .= $tei->inline('td', $count, $table_line_count, $table_sub_num, $cell_count);
					$body .= '|';
					$cell_count++;
				}
				$table_sub_num++;

				if ($calc_chk == 'on' && ($opt['row'] == 'sum' || $opt['row'] == 'average') ) $body .= "|";

				$body .= ($tr_edit) ? '|' : '';
				$body .= "h\n" . $body_table;
				$body_table = '';
				$table_header = 0;
			} else {
				$body .= $body_table;
				$body_table = '';
			}
			if ($calc_chk == 'on' && ($opt['column'] == 'sum' || $opt['column'] == 'average') ) $column_sum_or_avg = 1;
			if ( $table_end && $column_sum_or_avg){		//column
				$cell_calc = '';
				$body .= '|';
				$body .= $calc->calc_column($cell_count_max);
				$body .= ( $opt['edit'] == 'on' && $tr_edit ) ? '|' : '';
				$body .= "\n";
				$table_end = 0;
			}
			$body .= $args_line . "\n";
		}
	}

	$body = convert_html($body);	//06.11.17 sortabletable

	if(exist_plugin_convert('sortabletable') && isset($opt['sort']) && ! $td_edit) {
		$filter = isset($opt['filter']) ? 1 : 0;
		$table_id = "table_edit2_$count";
		$sort = new TableEdit2Sort;
		$sort->sort($opt['sort'],$r_cell_count);
		
		$body = sortabletable_main( $table_id, $body, $sort->sortabletableso, $filter);
	}

	$body = $head_button . $body;
	if (isset($opt['style'])){
		return preg_replace('/<div class="ie5"/', '<div style="' . $opt['style'] . '" class="ie5"', $body);
	} else {
		return $body;
	}
}

class TableEdit2Setting extends TableEdit2Form
{
//	var $opt;
	var $table_num;
	var $page;
//	var $s_page;
	var $script_uri;
	var $set_page = ':config/plugin/table_edit2/setting';
	var $opt_data = array();
	var $opt_data_sub = array();
	var $opt_key  = array();
	var $opt_msg  = array();
	var $table_data;

	function TableEdit2Setting($page ,$number)
	{
		$this->page		  = $page;
		$this->table_num  = $number;
//		$this->s_page = htmlspecialchars($page);
		$this->script_uri = get_script_uri();

		
	}
	function form($opt)
	{
		if (is_page($this->set_page, $reload=FALSE)) {
			$this->set_cfg();
		} else {
			$this->set_cfg_sub();
		}
		foreach ($opt as $o_key => $o_data) {
			$this->set_opt($o_key, $o_data);
		}
		return $this->set_form();
	}
	function set_form()
	{
		$input_opt = '';
		$input_opt .= $this->make_table('make_table');
		$input_opt .= $this->input_form('form');
		$input_opt .= $this->input_radio('edit',array('on','off'));
		$input_opt .= $this->input_radio('td_edit',array('on','off','edit','add'));
		$input_opt .= $this->input_radio('tr_edit',array('on','off','edit','add'));
		$input_opt .= $this->input_radio('table_mod',array('open','close','off'));
		$input_opt .= $this->input_radio('auth_check',array('off'));
		$input_opt .= $this->input_radio('csv',array(1,2,3,4,5,6));
		$input_opt .= $this->input_checkbox('textarea');
		$input_opt .= $this->input_text('title_c',1);
		$input_opt .= $this->input_text('title_r',1);
		$input_opt .= $this->input_checkbox('calc');
		$input_opt .= $this->input_checkbox('comma');
		$input_opt .= $this->input_text('dot',1);
		$input_opt .= $this->input_text('format_c',3);
		$input_opt .= $this->input_text('no_null',1);
		$set_ok = _('OK');
		$notimestamp_chk = _('no time stamp') . $this->checkbox('notimestamp', 1, 1);

		$body = <<<EOD
<form enctype="multipart/form-data" action="{$this->script_uri}" method="post">
 <div>
$input_opt
  <input type="hidden" name="plugin"    value="table_edit2" />
  <input type="hidden" name="table_num" value="{$this->table_num}" />
  <input type="hidden" name="edit_mod"  value="setting" />
  <input type="hidden" name="refer"     value="{$this->page}" />
  <input type="hidden" name="table_data"     value="{$this->table_data}" />
  <input type="submit" name="write"     value="$set_ok" />
  $notimestamp_chk
 </div>
</form>
EOD;

		return $body;
	}
	function input_radio( $name, $opt )
	{
		if ($this->opt_key[$name]) {
			$input = $this->radio(
				$opt,
				$name,
				$this->opt_data[$name][0]
				);
			$present = ($this->opt_key[$name] == 2) ? 1 : 0;
			return $this->field(
				$this->checkbox($name . '_exe', 'on', $present) . $name . $this->opt_msg[$name],
				$input);
		} else {
			return '';
		}
	}
	function make_table($name)
	{
		if ($this->opt_key[$name]) {
			$input .= $this->opt_data[$name][0] . ':'
				. $this->text($name . '_col',$this->opt_data_sub[$name][0],5) . '<br />';
			$input .= $this->opt_data[$name][1] . ':'
				. $this->text($name . '_row',$this->opt_data_sub[$name][1],5) . '<br />';
			$present = ($this->opt_key[$name] == 2) ? 1 : 0;
			return $this->field(
				$this->checkbox($name, 'on', $present) . $name . $this->opt_msg[$name],
				$input);
		} else {
			return '';
		}
	}
	function input_form( $name )
	{
		$input = '';
		if ($this->opt_key[$name]) {
			$count_max = count($this->opt_data[$name]);
			for ( $x = 0 ; $x < $count_max ; $x++ ) {
				$input .= $this->checkbox($name . $x . '_cb', 'on');
				$input .= $this->select(
					array('text','textarea','select','radio'),
					$name . $x . '_s',
					$this->opt_data[$name][$x]
					);
				$input .= $this->text($name . $x . '_t',$this->opt_data_sub[$name][$x],40) . '<br />';
			}
			$present = ($this->opt_key[$name] == 2) ? 1 : 0;
			return $this->field(
				$this->checkbox($name . '_exe', $count_max, $present) . $name . $this->opt_msg[$name],
				$input);
		} else {
			return '';
		}
	}
	function input_text( $name, $size )
	{
		if ($this->opt_key[$name]) {
			$input = $this->text(
				$name,
				$this->opt_data[$name][0],
				$size
				);
			$present = ($this->opt_key[$name] == 2) ? 1 : 0;
			return $this->checkbox($name . '_exe', 'on', $present)
				. $name . $this->opt_msg[$name] . $input . '<br />';
		} else {
			return '';
		}
	}
	function input_checkbox( $name )
	{
		if ($this->opt_key[$name]) {
			$present = ($this->opt_key[$name] == 2) ? 1 : 0;
			return $this->checkbox($name . '_exe', 'on', $present)
				 . $name . $this->opt_msg[$name] . '<br />';
		} else {
			return '';
		}
	}
	function set_cfg()
	{
		$set_s  = get_source($this->set_page);
		$set_key = '';
		foreach ($set_s as $s_line) {
			preg_match('/^(\*{1,3})([a-z_]+)\s?(\d?)(.*)\[#(.*?)\](.*?)$/i',$s_line,$key);
			if ( $key[2] !== '' && isset($key[2]) ) {
				$this->opt_key["{$key[2]}"] = $key[3];
				$this->opt_msg["{$key[2]}"] = $key[4];
				$set_key = $key[2];
			}
			if (preg_match('/^\|(.+)\|([hHfFcC]?)$/', $s_line, $key_opt)){
				if (! isset($key_opt[2]) || $key_opt[2] == '') {
					$this->set_opt($set_key, $key_opt[1]);
				}
			}
		}
	
	}
	function set_cfg_sub()
	{
		$this->opt_key['make_table'] = 2;
		$this->opt_data['make_table'][0] = 'column';
		$this->opt_data['make_table'][1] = 'row';
		$this->opt_data_sub['make_table'][0] = 3;
		$this->opt_data_sub['make_table'][1] = 3;
		$this->opt_key['edit'] = 1;
		$this->opt_key['table_mod'] = 2;
		$this->opt_data['table_mod'][0] = 'open';
		$this->opt_key['title_c'] = 1;
		$this->opt_data['title_c'][0] = 1;
	}
	function set_opt($key, $data)
	{
		$data_h = explode("|", $data);
		$this->opt_data[$key][] = array_shift($data_h);
		$this->opt_data_sub[$key][] = array_shift($data_h);
//		foreach($data_h as $data_s) {
//			$this->opt_data[$key][] = $data_s;
//		}
	}
}
class TableEdit2Csv extends TableEdit2Form
{
	var $page = '';
	var $count = 0;

	function TableEdit2Csv($page, $count)
	{
		$this->page = $page;
		$this->count = $count;
	}
	function set_csv($mode)
	{
		$r_char_in = '';
		$r_char_out = '';
		$w_quote = '';
		$end_of_line = '';
		$notimestamp_chk = '';
		$maxsize = PLUGIN_TABLE_EDIT2_MAX_FILESIZE;
		$script_uri = get_script_uri();
		$char_data = array('SJIS', 'UTF-8', 'EUC-JP');
		if ($mode == 'import') {
			$cancel = 'im_cancel';
			$msg_maxsize = sprintf("max file size %s", number_format($maxsize/1024) . 'KB');
			$r_char_in = $this->field(_('character code in'), $this->radio($char_data, 'charset_in', 'SJIS'));
			$notimestamp_chk = _('no time stamp') . $this->checkbox('notimestamp', 1, 1);
			$file_save = <<<EOD
  <input type="hidden" name="write"     value="OK" />
  <input type="hidden" name="max_file_size" value="$maxsize" />
   <span class="small">
	$msg_maxsize
   </span><br />
   <label for="_table_edit2_csv_file">file:</label> <input type="file" name="table_edit2_csv_file" id="_table_edit2_csv_file" />
EOD;
		} else if ($mode == 'export') {
			$cancel = 'ex_cancel';
			$r_char_out = $this->field(_('character code out'), $this->radio($char_data, 'charset_out', 'SJIS'));
			$w_quote_data = array(
				'no' => _('no'),
				'moji' => _('character'),
				'retu' => _('column')
				);
			$w_quote = $this->radio($w_quote_data, 'w_quote', 'no', 0);
			$column_w_q_text = $this->text('column_w_q','1,3,4');
			$w_quote = $this->field(_('w quote'),$w_quote . ' (' . _('column number') . ' ' . $column_w_q_text . ')');

			//end of line
			$line_feed_code = _('line feed code');
			$end_of_line_data = array(
				'win' => 'Windows(CRLF)',
				'unx' => 'UNIX(LF)',
				'mac' => 'Macintosh(CR)',
				);
			$end_of_line = "<span>$line_feed_code</span>"
				 . $this->select($end_of_line_data, 'end_of_line', 'win', 0);
		}

		$body = <<<EOD
<form enctype="multipart/form-data" action="$script_uri" method="post">
 <div>
  $r_char_in
  $r_char_out
  $w_quote
  $end_of_line
  $notimestamp_chk
 </div>
 <div>
  <input type="hidden" name="plugin" value="table_edit2" />
  <input type="hidden" name="refer"  value="{$this->page}" />
  <input type="hidden" name="table_num"  value="{$this->count}" />
  $file_save
  <input type="submit" name="csv_mod" value="$mode" />
  <input type="submit" name="$cancel"  value="cancel" />
 </div>
</form>
EOD;

		return $body;
	}
}
class TableEdit2Indicate
{
	var $script_uri;
	var $page;
	var $count;
	var $set_csv = '';
//	var $table_f_chose;
	
	function TableEdit2Indicate($page, $count)
	{
		$this->script_uri = get_script_uri();
		$this->page = $page;
		$this->count = $count;
	}
	function open_close( $mode, $edit )
	{
		$s_table_close = _('close');
		$s_table_open  = _('open');
		if ($mode == 'open' || $mode == ''){
			$image_png = $this->img( 'close.png', 11, 11, $s_table_close);
			$table_mod = 'close';
		} else if ($mode == 'close'){
			$image_png = $this->img( 'paraedit.png', 9, 9, $s_table_open);
			$edit = 'off';
			$table_mod = 'open';
		}
		return <<<EOD
<div style="float:right;" id="TableEdit2TableNumber{$this->count}">
 <a href="{$this->script_uri}?plugin=table_edit2&amp;refer={$this->page}&amp;table_mod=$table_mod&amp;table_num={$this->count}">$image_png</a>
{$this->set_csv}
</div>
<div style="float:both;"></div>
EOD;
	}
	function csv_button( $csv )
	{
		$import_title = _('import');
		$import = $this->link_s($this->img( 'import.png', 11, 11, $import_title), 'import');
		$export_title = _('export');
		$export = $this->link_s($this->img( 'export.png', 11, 11, $export_title), 'export');

		switch ( $csv ) {
			case 1:
				$this->set_csv = "\n" . $import . "\n" . $export . "\n";
				break;
			case 2:
				$this->set_csv = "\n" . $import . "\n";
				break;
			case 3:
				$this->set_csv = "\n" . $export . "\n";
				break;
			case 4:
				$this->set_csv = "<br />\n" . $import . "<br />\n " . $export . "\n";
				break;
			case 5:
				$this->set_csv = "<br />\n" . $import . "\n";
				break;
			case 6:
				$this->set_csv = "<br />\n" . $export . "\n";
				break;
		}
	}
	function img( $image, $width, $height, $title)
	{
		$image_uri = IMAGE_URI;
		$img_tag = <<<EOD
  <img src="{$image_uri}plus/$image" width="$width" height="$height" alt="" title="$title" />
EOD;
		return $img_tag;
	}
	function link_s( $image, $csv_mode )
	{
		return <<<EOD
 <a href="{$this->script_uri}?plugin=table_edit2&amp;refer={$this->page}&amp;table_num={$this->count}&amp;set_csv=$csv_mode">$image</a>
EOD;
	}
	function inline( $edit_mod, $count,$line_count, $table_sub_num = NULL, $cell_count = NULL)
	{
		
		$table_inline = 'edit_mod=' . $edit_mod;
		$table_inline .= ',table_num=' . $count;
		$table_inline .= ',line_count=' . $line_count;
		if (isset($table_sub_num)) $table_inline .= ',table_sub_num=' . $table_sub_num;
		if (isset($cell_count)) $table_inline .= ',cell_count=' . $cell_count;
//		$table_inline .= ',table_f_chose=' . $this->table_f_chose;
		
		return '&table_edit2(' . $table_inline . ');';
	}
}
class TableEdit2Form
{
	var $c_count = 0;
	var $select_chk;
	var $radio_chk;
	var $bgcolor = array();
	var $no_null = 0;

	function bgcolor($bg_color)
	{
		$this->c_count = count($bg_color);
		if ($this->c_count){
			if ($this->c_count > 1) {
				foreach($bg_color as $color){
					$color = htmlspecialchars($color);
					$this->bgcolor[] = 'style="background-color:' . $color . ';"';
				}
			} else {
				$this->bgcolor =  array_fill(0, 30, ' style="background-color:' . $bg_color[0] . ';"');
			}
		} else {
			$this->bgcolor = array_fill(0, 30, '');
		}
	}
	function select($select_data, $s_name, $present, $equal = 1)
	{
		$select_list = '';
		$count = 0;
		foreach($select_data as $key => $s_data) {
			$key_data = $equal ? $s_data : $key;
			$selected = ($present == $key_data) ? ' selected="selected"' : '';
			if ( $selected != '' ) $this->select_chk = 1;
			$select_list .= ($this->no_null && $key_data == '') ?
				'' : "    <option value=\"$key_data\"{$this->bgcolor[$count]}$selected>$s_data</option>\n";
			$count++;
		}
		$select = "   <select name=\"$s_name\">$select_list</select>\n";
		return $select;
	}
	function radio($radio_data, $r_name, $present, $equal = 1)
	{
		$radio_list = '';
		$count = 0;
		foreach($radio_data as $key => $r_data) {
			$key_data = $equal ? $r_data : $key;
			$checked = ($present == $key_data) ? ' checked="checked"' : '';
			if ( $checked != '' ) $this->radio_chk = 1;
			$radio_list .= ($this->no_null && $key_data == '') ?
				'' : "  <input type=\"radio\" name=\"$r_name\" value=\"$key_data\"{$this->bgcolor[$count]}$checked />$r_data\n";
			$count++;
		}
		return $radio_list;
	}
	function checkbox($name, $value, $present = 0)
	{
		$checked = ($present) ? ' checked="checked"' : '';
		$checkbox = '<input type="checkbox" name="' . $name
			. '" value="' . $value . '"' . $this->bgcolor[0] . $checked . ' />';
		return $checkbox;
	}
	function text($name, $value = '', $size = null)
	{
		$size = ( isset($size) ) ? ' size="' . $size . '"' : '';
		$text = '<input type="text" name="' . $name
			. '" value="' . $value . '"' . $size . $this->bgcolor[0] . ' />';
		return $text;
	}
	function textarea($name, $value = '', $rows = 2,$cols = 40)
	{
		$textarea = '<textarea name="' . $name
			. '" rows="' . $rows . '" cols="' . $cols . '">' . $value . '</textarea>';
		return $textarea;
	}
	function f_input($type, $name, $value)
	{
		return '   <input type="' . $type . '" name="' . $name . '"  value="' . $value . '" />';
	}
	function field($name, $data)
	{
		$field = <<<EOD
 <div>
  <fieldset><legend>$name</legend>$data</fieldset>
 </div>
EOD;

		return $field;
	}
}
class TableEdit2Auth
{
	function basic_auth()
	{
		global $realm;
		
		if ($realm == '') {
			global $_msg_auth;
			$m_auth = $_msg_auth;
		} else {
			$m_auth = $realm;
		}
		unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
		header( 'WWW-Authenticate: Basic realm="'.$m_auth.'"' );
		header( 'HTTP/1.0 401 Unauthorized' );
		return auth::check_auth();
	}
}
function table_edit2_auth()
{
	class auth {
		function check_auth()
		{
			foreach (array('PHP_AUTH_USER', 'AUTH_USER', 'REMOTE_USER', 'LOGON_USER') as $x) {
				if (isset($_SERVER[$x]) && ! empty($_SERVER[$x])) {
					if (! empty($_SERVER['AUTH_TYPE']) && $_SERVER['AUTH_TYPE'] == 'Digest') return $_SERVER[$x];
					$ms = explode('\\', $_SERVER[$x]);
					if (count($ms) == 3) return $ms[2]; // DOMAIN\\USERID
					foreach (array('PHP_AUTH_PW', 'AUTH_PASSWORD', 'HTTP_AUTHORIZATION') as $pw) {
						if (! empty($_SERVER[$pw])) return $_SERVER[$x];
					}
				}
			}
		}
		function check_role()
		{
			return PKWK_READONLY;
		}
		function auth_pw($auth_users)
		{
			$user = '';
			foreach (array('PHP_AUTH_USER', 'AUTH_USER') as $x) {
				if (isset($_SERVER[$x])) {
					$ms = explode('\\', $_SERVER[$x]);
					if (count($ms) == 3) {
						$user = $ms[2]; // DOMAIN\\USERID
					} else {
						$user = $_SERVER[$x];
					}
					break;
				}
			}

			$pass = '';
			foreach (array('PHP_AUTH_PW', 'AUTH_PASSWORD') as $x) {
				if (! empty($_SERVER[$x])) {
					$pass = $_SERVER[$x];
					break;
				}
			}

			if (empty($user) && empty($pass)) return 0;
			if (empty($auth_users[$user])) return 0;
			if ( pkwk_hash_compute($pass, $auth_users[$user]) !== $auth_users[$user]) return 0;
			return 1;
		}
	}
}
function table_edit2_message()
{
	function _($message){ return $message; }
}
class TableEdit2Calc
{
	var $comma;
	var $dot;
	var $c_format;
	var $c_abs;
	var $m_abs;
	var $cell_format = 'RIGHT: ';
	var $body_table;
	var $cells;
	var $line_count;
	var $cell_count;
	var $calc_cell_count;
	var $row;
	var $column;

	function calc($match_cells)
	{
		$cell_count = 1;
		$this->calc_cell_count = 0;
		$this->body_table = '|';
		foreach ($match_cells as $cell){
			$exp = trim(mb_convert_kana( $cell, "a"));
			if ( $exp == '' ) $cell = '';
			if ( preg_match_all('/\@([\w]+)\((?:(\d+),(\d+)|([rc]))\)/', $exp, $matc_cell)){		//func 06.09.13
				$exp = $this->cell_func($matc_cell,$cell_count,$exp);
				$cell = $exp;
			}
			if (preg_match('/^[\d\s\+\-\(]+[\d\.\s\+\-\*\/\%\(\)]*$/', $exp) && preg_match('/[\d\s\)]+$/', $exp) && $exp != ''){
				eval("\$cell = $exp;");
				$cell_abs = $this->c_abs[$cell_count] ? abs($cell) : $cell;
				$this->body_table .= $this->c_format[$cell_count] ? $this->format($cell_abs) : $this->no_format($cell_abs);
				if ($this->m_abs) $cell = $cell_abs;
				$this->calc_cell_count++;
			} else {
				$this->body_table .= $cell . '|';
			}
			$this->cells[$this->line_count][$cell_count] = $cell;
			$cell_count++;
		}
		$this->cell_count = $cell_count;
	}
	function calc_row()
	{
		if ( $this->row == 'sum' || $this->row == 'average' ){		//row
			if ($this->row == 'sum') $cell_calc = array_sum( $this->cells[$this->line_count] );
			if ($this->row == 'average') $cell_calc = $this->avg();
			$this->cells[$this->line_count][$this->cell_count] = $cell_calc;
			$this->body_table .= $this->format($cell_calc);
			$this->cell_count++;
		}
	}
	function avg()
	{
		if ( $this->calc_cell_count === 0 ){
			return '';
		} else {
			return array_sum($this->cells[$this->line_count]) / $this->calc_cell_count;
		}
	}
	function format($cell_calc)
	{
		return $this->cell_format . number_format((float) $cell_calc, $this->dot, '.', $this->comma) . '|';
	}
	function no_format($cell_calc)
	{
		return $this->cell_format . $cell_calc . '|';
	}
	function opt_c($col, $count)
	{
		$data = array_fill(0, $count + 1, 0);
		if ( strpos($col, '|') === FALSE ) {
			$data[$col] = 1;
		} else {
			$m =explode('|', $col);
			foreach ($m as $m_line) {
				$data[$m_line] = 1;
			}
		}
		return $data;
	}
	function calc_column($cell_count_max)
	{
		for ( $x = 1 ;$x < $cell_count_max ;$x++){
			$cell_column = 0;
			for ( $y = 1 ;$y < $this->line_count ;$y++){
				$cell_column += $this->cells[$y][$x];
			}
			if ($this->column == 'sum' ) {
				$cell_calc = $cell_column;
			} else if ($this->column == 'average'){
				$cell_calc = $cell_column / ( $this->line_count - 1);
			}
			$body .= $this->format($cell_calc);
		}
		return $body;
	}
//}
//class TableEdit2Func extends TableEdit2Calc
//{
	function cell_func($matc_cell,$cell_count,$cell)
	{
		$func_s = $func_r = array();
		for ($i=0; $i< count($matc_cell[0]); $i++) {
			$func_name = "sum_r sum_c cell cell_ra avg_r avg_c count_r count_c max_r max_c min_r min_c sum count number";
			if ( preg_match("/\b" . $matc_cell[1][$i] . "\b/i", $func_name ) ) {
				$first = (strpos($matc_cell[1][$i], '_') || $matc_cell[1][$i] === 'cell') ? 2 : 4;
				$cell_func = call_user_func_array( array('TableEdit2Func', strtolower($matc_cell[1][$i])),array($matc_cell[$first][$i],$matc_cell[3][$i],$this->cells,$this->line_count,$cell_count));
			}
			$func_s[] = ($first === 4) ? '/\@' . $matc_cell[1][$i] . '\(' . $matc_cell[$first][$i] . '\)/' :
				'/\@' . $matc_cell[1][$i] . '\(' . $matc_cell[2][$i] . ',' . $matc_cell[3][$i] . '\)/';
			$func_r[] = '' . $cell_func . '';
		}
		$cell = preg_replace($func_s, $func_r, $cell);
		return $cell;
	}
}
class TableEdit2Func
{
	function sum($start,$end,$cells,$line_count,$cell_count)
	{
		$cell_sum = 0;
		$end = ($start == 'c') ? $line_count : $cell_count;
		$position = ($start == 'c') ? $cell_count : $line_count;
		for ($x = 1;$x <= $end;$x++){
			$cell_sum += ($start == 'c') ? $cells[$x][$position]:
				$cells[$position][$x];
		}
		return $cell_sum;
	}
	function count($start,$end,$cells,$line_count,$cell_count)
	{
		$count = 0;
		$end = ($start == 'c') ? $line_count : $cell_count;
		$position = ($start == 'c') ? $cell_count : $line_count;
		for ($x = 1;$x <= $end;$x++){
			$cell_chk = ($start == 'c') ? $cells[$x][$position]:
				$cells[$position][$x];
			if (is_numeric($cell_chk)) $count++;
		}
		return $count;
	}
	function number($start,$end,$cells,$line_count,$cell_count)
	{
		$count = ($start == 'r') ? $line_count : $cell_count;
		return $count;
	}
	function sum_r($start,$end,$cells,$line_count,$cell_count)
	{
		$cell_sum = 0;
		for ( $x = $start;$x <= $end;$x++){
			$cell_sum += $cells[$line_count][$x];
		}
		return $cell_sum;
	}
	function sum_c($start,$end,$cells,$line_count,$cell_count)
	{
		$cell_sum = 0;
		for ( $x = $start;$x <= $end;$x++){
			$cell_sum += $cells[$x][$cell_count];
		}
		return $cell_sum;
	}
	function cell($row,$column,$cells,$line_count,$cell_count)
	{
		$cell = $cells[$row][$column];
		return $cell;
	}
	function cell_ra($row,$column,$cells,$line_count,$cell_count)
	{
		$cell = $cells[$line_count - $row][$cell_count - $column];
		return $cell;
	}
	function avg_r($start,$end,$cells,$line_count,$cell_count)
	{
		$cell_sum = 0;
		$sum_count = 0;
		for ( $x = $start;$x <= $end;$x++){
			$cell_sum += $cells[$line_count][$x];
			if (is_numeric($cells[$line_count][$x])){
				$sum_count++;
			}
		}
		$cell_avg = ( $sum_count === 0 ) ? '' : $cell_sum / $sum_count;
		return $cell_avg;
	}
	function avg_c($start,$end,$cells,$line_count,$cell_count)
	{
		$cell_sum = 0;
		$sum_count = 0;
		for ( $x = $start;$x <= $end;$x++){
			$cell_sum += $cells[$x][$cell_count];
			if (is_numeric($cells[$x][$cell_count])){
				$sum_count++;
			}
		}
		$cell_avg = ( $sum_count === 0 ) ? '' : $cell_sum / $sum_count;
		return $cell_avg;
	}
	function count_r($start,$end,$cells,$line_count,$cell_count)
	{
		$count = 0;
		for ( $x = $start;$x <= $end;$x++){
			if (is_numeric($cells[$line_count][$x])){
				$count++;
			}
		}
		return $count;
	}
	function count_c($start,$end,$cells,$line_count,$cell_count)
	{
		$count = 0;
		for ( $x = $start;$x <= $end;$x++){
			if (is_numeric($cells[$x][$cell_count])){
				$count++;
			}
		}
		return $count;
	}
	function max_r($start,$end,$cells,$line_count,$cell_count)
	{
		$max_cell = $cells[$line_count][$start];
		$start++;
		for ( $x = $start;$x <= $end;$x++){
			if ($max_cell < $cells[$line_count][$x]){
				$max_cell = $cells[$line_count][$x];
			}
		}
		return $max_cell;
	}
	function max_c($start,$end,$cells,$line_count,$cell_count)
	{
		$max_cell = $cells[$start][$cell_count];
		$start++;
		for ( $x = $start;$x <= $end;$x++){
			if ($max_cell < $cells[$x][$cell_count]){
				$max_cell = $cells[$x][$cell_count];
			}
		}
		return $max_cell;
	}
	function min_r($start,$end,$cells,$line_count,$cell_count)
	{
		$min_cell = $cells[$line_count][$start];
		$start++;
		for ( $x = $start;$x <= $end;$x++){
			if ($min_cell > $cells[$line_count][$x]){
				$min_cell = $cells[$line_count][$x];
			}
		}
		return $min_cell;
	}
	function min_c($start,$end,$cells,$line_count,$cell_count)
	{
		$min_cell = $cells[$start][$cell_count];
		$start++;
		for ( $x = $start;$x <= $end;$x++){
			if ($min_cell > $cells[$x][$cell_count]){
				$min_cell = $cells[$x][$cell_count];
			}
		}
		return $min_cell;
	}
}
class TableEdit2Sort
{
	var $sortabletableso;

	function sort($sort_opt, $r_cell_count)
	{
		if ($sort_opt == '') {
			$this->sortabletableso = join(',', array_fill(0, $r_cell_count, "'String'"));
		} else {
			$st_so = explode("|", $sort_opt);
			$st_so = preg_replace('/^(\S{1})/e',"strtoupper('\\1')",$st_so); //>> ucfirst
//			$st_so = ucfirst($st_so);
			$this->sortabletableso = "'" . array_shift($st_so) . "'";
			foreach ($st_so as $st_so_c) {
				$this->sortabletableso .= ",'" . $st_so_c . "'";
			}
		}
	}
}
function plugin_table_edit2_inline()
{
	global $vars, $edit_auth_pages;
	$script_uri = get_script_uri();
	$page = isset($vars['page']) ? $vars['page'] : '';
	$r_page = rawurlencode($page);
	$body = '';
	$opt = array(
		'table_sub_num' => '',
		'cell_count' => '',
		);
	if (! function_exists('_')) table_edit2_message();

	$s_table_edit = _('edit');
	$s_table_add  = _('addition');

	$args = func_get_args();
	foreach ($args as $opt_key) {
		list($key, $val) = explode('=', strtolower($opt_key));
		$opt[$key] = htmlspecialchars($val);
	}

	$add_show = '';
	$edit_mod = $opt['edit_mod'];
	if (PLUGIN_TABLE_EDIT2_ADD_SHOW && ( $edit_mod == 'td' || $edit_mod == 'tr' )) {
		if ($edit_mod == 'tr') $edit_mod = 'show';
		if ($edit_mod == 'td') $edit_mod = 'tdshow';
		$add_show = '&amp;add_show=1';
	}

	if ($opt['edit_mod'] == 'show' || $opt['edit_mod'] == 'tdshow'){
		$image_png = 'paraedit.png" width="9" height="9" alt="" title="' . $s_table_edit . '"';
	} else if ($opt['edit_mod'] == 'tr' || $opt['edit_mod'] == 'td'){
		$image_png = 'mini_add.png" width="8" height="8" alt="" title="' . $s_table_add . '"';
	}
	$body .= '<a href="' . $script_uri . "?plugin=table_edit2&amp;refer=$r_page&amp;edit_mod="
		 . $edit_mod . "&amp;table_num=" . $opt['table_num'] . "&amp;table_sub_num=" . $opt['table_sub_num']
		 . "&amp;line_count=" . $opt['line_count'] . "&amp;cell_count=" . $opt['cell_count'] . $add_show
//		 . "&amp;table_f_chose=" . $opt['table_f_chose']
		 . '"><img src="' . IMAGE_URI . 'plus/' . $image_png . ' /></a>';
	return $body;
}
function plugin_table_edit2_action()
{
	global $vars, $post, $auth_users;
	$table_num = 	$vars['table_num'];
	$page = 		$vars['refer'];
	$script_uri = get_script_uri();

	if (is_freeze($page)) check_editable($page, true, true);

	//	Cancel
	$anchr_jump = ( PLUGIN_TABLE_EDIT2_ANCHR_JUMP ) ? '#TableEdit2TableNumber' . $table_num : '';
	if (isset($vars['cancel'])) {
		header('Location: ' . $script_uri . '?' . rawurlencode($page) . $anchr_jump);
		exit;
	}

	$line_count = 1;
	$table_sub_num = 1;			//td
	$table_sub_num_chk = 1;		//td
	$setting = 0;
	$import = $export = $csv_cancel = 0;

	if (! function_exists('_')) table_edit2_message();

	$td_edit = ($vars['edit_mod'] == 't_edit_td' || $vars['edit_mod'] == 'td') ? 1 : 0;
	$tr_edit = ($vars['edit_mod'] == 't_edit' || $vars['edit_mod'] == 'tr') ? 1 : 0;
	$t_edit = ($vars['edit_mod'] == 't_edit_td' || $vars['edit_mod'] == 't_edit') ? 1 : 0;
	$edit_show = ($vars['edit_mod'] == 'tdshow' || $vars['edit_mod'] == 'show') ? 1 : 0;

	// Petit SPAM Check (Client(Browser)-Server Ticket Check)
	$spam = FALSE;
	if (! function_exists('honeypot_write') && $t_edit )
		 $spam = plugin_table_edit2_spam($post['encode_hint']);
	if ($spam) return plugin_table_edit2_honeypot();

	if (! class_exists('auth')) { table_edit2_auth(); }
	if (auth::check_role('readonly')) die_message('PKWK_READONLY prohibits editing');
//	if (PKWK_READONLY == ROLE_AUTH && auth::get_role_level() > ROLE_AUTH)
//		die_message( _('PKWK_READONLY prohibits editing') );
	if ( PLUGIN_TABLE_EDIT2_HTTP_REFERER ) {
		if (! function_exists('path_check')) {
			if (! ereg('^(' . $script_uri . ')',$_SERVER['HTTP_REFERER'])) return;
		} else {
			if (! path_check($script_uri,$_SERVER['HTTP_REFERER'])) return;
		}
	}

	if ( $vars['edit_mod'] === 'setting' ) {
		$set = new TableEdit2SettingWrite($vars);
		if (!$set->sc) return $set->error;
		$setting = 1;
		unset ($vars['table_mod']);
	}
	
	if ( isset($vars['csv_mod']) || isset($vars['ex_cancel']) || isset($vars['im_cancel']) || isset($vars['set_csv']) || isset($vars['csv_back'])) {
		$csv = new TableEdit2CsvAction;
		if ($vars['csv_mod'] === 'import') {
			$csv->csv_import($vars);
			$import = 1;
		} else if ($vars['csv_mod'] === 'export') {
			$export = 1;
			$csv_export_data = array();
		} else if (isset($vars['ex_cancel']) || isset($vars['im_cancel'])) {
			$csv_cancel = 1;
			$notimestamp = TRUE;
		} else if (isset($vars['set_csv'])) {
			$set_csv = 1;
			$notimestamp = TRUE;
		} else if (isset($vars['csv_back'])) {
			if ( PLUGIN_TABLE_EDIT2_CSV_UNLINK ) {
				$con = new TableEdit2CsvConversion($page, array( 'name' => $vars['file_name']) );
				unlink($con->filename);
				unlink($con->logname);
			}
			header('Location: ' . $script_uri . '?' . rawurlencode($page));		// . $anchr_jump
			exit;
		} else {
			return array('msg' => 'csv error','body' => 'csv option error');	// . join("\n", $csv_data) 
		}
	}
	
	if ( isset($vars['table_mod']) ) $chg = new TableEdit2TableMod($vars['table_mod']);
	if ( $td_edit || $tr_edit ) $edit = new TableEdit2Edit( $vars );
	if ( $edit_show ) $show = new TableEdit2Show( $vars, $page );

	$args  = get_source($page);
	static $count = 0;
	$source_s = '';
	$body = '';
	$row_title = 0;

	if( $td_edit || $tr_edit || $setting || $import)
		$notimestamp = (isset($vars['notimestamp'])) ? TRUE : FALSE;

	foreach ($args as $args_key => $args_line) {

		preg_match('/^#([^\(\{]+)(?:\(([^\r]*)\))?(\{*)/', $args_line, $matches);
		if($matches[1] == 'table_edit2' || $matches[1] == "table_edit2\n") {
			$table_find = 1;
			$count++;
			if( $line_count === 1 && $count == $table_num ){

				if(preg_match('/auth_check[=_](on|off)/i',$matches[2],$auth_check)) {
					if ( $auth_check[1] == 'on' ) {
						if (!auth::auth_pw($auth_users)) {
							$user = TableEdit2Auth::basic_auth();
							if(empty($user)) return;
						}
					}
				} else {
					check_editable($page, true, true);
				}

				if( $setting ) $args_line = $set->plugin_set_opt($matches[3]);

				if( $import )  $args_line = $csv->import_data_set($matches[2], $matches[3]);
				if( isset($vars['ex_cancel']) )  $args_line = $csv->cancel($matches[2], $matches[3], 'export');
				if( isset($vars['im_cancel']) )  $args_line = $csv->cancel($matches[2], $matches[3], 'import');
				if( $set_csv )  $args_line = $csv->set_csv_opt($matches[2], $matches[3], $vars['set_csv']);

				if($vars['edit_mod'] == 'tdshow')		//tdshow - td_title - 06.11.11
					if(preg_match('/title_c=(\d+)/i',$matches[2],$match_title))
						$td_title_count = $match_title[1] - 1;

				if($vars['edit_mod'] == 'show')					//show				header
					if(preg_match('/title_r=(\d+)/i',$matches[2],$m_row_title))
						$row_title = $m_row_title[1];

				if( $edit_show ) $show->text_type( $matches[2] );

				if ( isset($vars['table_mod']) ){		//table_mod
					$notimestamp = TRUE;
					$args_line = $chg->table_mod_chg($matches, $args_line);
				}
			}
			$end_line = strlen($matches[3]);
		}

		if (preg_match('/^\}{' . $end_line . '}/', $args_line) || !$end_line) $table_find = 0;

		if($table_find && $table_num == $count && !isset($vars['table_mod']) && !$setting && !$import){

			$table_f_chose = 0;
			$table_f_chose = (preg_match('/^\|(.+)\|([hHfFcC]?)$/', $args_line, $match_line)) ? 1 : 0;
			if ($args_line{0} == ',' && $args_line != ',') $table_f_chose = 2;
			if ( $td_edit || $tr_edit ) $edit->chose = $table_f_chose;

			if ($table_f_chose){

				if ($table_f_chose === 1) {
					$match_t = explode("|", $match_line[1]);
				} elseif ($table_f_chose === 2) {
					$match_t = csv_explode(',', substr(str_replace("\n", '', $args_line), 1));
					$match_line = array( 1 => join(',', $match_t), 2 => '' );
				}

				if ($export) $csv_export_data[] = $match_line[1];

				if ($table_sub_num == $vars['table_sub_num'] && $table_sub_num_chk){		//td 06.09.18
					$show->chk_table_sub_first_line = $line_count;
					$table_sub_num_chk = 0;
				}

				if ($vars['line_count'] == $line_count || strtolower( $match_line[2] ) == 'h' || $vars['edit_mod'] == 'tdshow' || $td_edit || $row_title){
//					$match_t = explode("|", $match_line[1]);
					if($vars['edit_mod'] == 'tdshow')		//tdshow - td_title - 06.11.11
						$show->td_title[$line_count] = $match_t[$td_title_count];
				}

				if($vars['edit_mod'] == 'show') {					//show				header
					if($match_line[2] == 'h' && !$row_title) $show->table_header($match_t);
					if($line_count == $row_title) $show->table_header($match_t);
				}
				if ( $vars['line_count'] == $line_count || $table_sub_num == $vars['table_sub_num'] )		// textarea 06.11.12
					if( $edit_show )
						if($show->t_type == 'textarea') $show->text_type_textarea(count($match_t));

				if ( $td_edit && $table_sub_num_chk == 0 && $table_sub_num == $vars['table_sub_num'])
				{
					$source_s .= $edit->td_edit( $match_t ) . $match_line[2] . "\n";
					$table_sub_num_count_chk = 1;

				} else if($vars['line_count'] == $line_count && ! $td_edit)
				{
					if( $tr_edit ) {				//t_edit tr_add
						if ($vars['add_show']) {
							$source_s .= $args_line;
							if ( $edit->chose !== 2 ) $edit->chk_csv_source($args, $args_key);
						}
						$source_s .= $edit->tr_edit($args_line, $match_t, $match_line[2]);

					} else if( $edit_show ){		//show or tdshow
						$show->line_count = $line_count;
						$body = $show->show_mod($match_t);
					}

				} else {
					if ($vars['edit_mod'] == 'tdshow')		//tdshow and edit_td
						$show->cells[$line_count] = $match_t;

					$table_sub_num_count_chk = 1;			//td06.09.18
					$source_s .= $args_line;
				}
				$line_count++;
			} else {
				if ($table_sub_num_count_chk == 1 && substr($args_line, 0, 2) != '//'){			//td
					$table_sub_num++;
					$table_sub_num_count_chk = 0;
				}
				$source_s .= $args_line;
			}
		} else {
			$source_s .= $args_line;
		}
	}

	if ($export) return $csv->csv_export($vars, $csv_export_data);

	$collision = 0;
	if ($tr_edit || $td_edit) {
		if (md5(get_source($vars['refer'], TRUE, TRUE)) != $vars['digest']) {
			$title =  _("On updating  $1, a collision has occurred.");
			$body  =  _("It seems that someone has already updated the page you were editing.<br />") .
					  _("Your editing contents updated, but there is a possibility that overwrote.<br />")
					   . make_pagelink($vars['refer']);
			$collision = 1;
		}
	}

	if ($tr_edit || $td_edit || isset($vars['table_mod']) || $setting || $import || $csv_cancel || $set_csv)
		page_write($page, $source_s, $notimestamp);

	$get['page'] = $post['page'] = $vars['page'] = $page;

	if ( $collision ) return array('msg'=>$title, 'body'=>$body);

	if ( $edit_show ) return array('msg'=>$show->title, 'body'=>$body);

	header('Location: ' . $script_uri . '?' . rawurlencode($page) . $anchr_jump);
	exit;
}
class TableEdit2TableMod
{
	var $table_mod;
	var $search_r = array ();
	var $replace_r = array ();
	
	function TableEdit2TableMod($mod)
	{
		$this->table_mod = $mod;
		if ($this->table_mod == 'close'){
			$this->search_r = array ('@table_mod=open@si');
			$this->replace_r = array ('table_mod=close');
		} else if ($this->table_mod == 'open') {
			$this->search_r = array ('@table_mod=close@si');
			$this->replace_r = array ('table_mod=open');
		}
	}
	function table_mod_chg($matches, $args_line)
	{
		if(preg_match('/(table_mod)/',$matches[2])){
			return preg_replace($this->search_r, $this->replace_r, $args_line);
		} else {
			return "#table_edit2(" . $matches[2] . ",table_mod=" . $this->table_mod . ")" . $matches[3] . "\n";
		}
	}
}
class TableEdit2SettingWrite
{
	var $set_opt = '';
//	var $surround;
	var $sc;
	var $error = array();
	var $table_data = '';
	
	function TableEdit2SettingWrite($opt)
	{
		$this->sc = 0;
		$s_opt = array();
		foreach ($opt as $key => $data) {
			if (preg_match('/([a-z_]+)_exe/',$key,$set)) {
				$this->sc = 1;
				if ($set[1] == 'form') {
					$form = array();
					for ($x = 0;$x < $data;$x++) {
						if (isset($opt['form' . $x . '_cb'])) {
							$form[] = $opt['form' . $x . '_s'] . '=' . $opt['form' . $x . '_t'];
						}
					}
					$s_opt[] = 'form=' . join('|',$form);
				} else {
					$s_opt[] = $set[1] . '=' . $opt[$set[1]];
				}
			}
		}
		$this->set_opt = '#table_edit2(' . join(',', $s_opt) . ')';
		if (!$this->sc && !$opt['make_table'] == 'on') $this->error($opt);
		$this->table_data = ($opt['make_table'] == 'on') ?
			 $this->make_table($opt['make_table_col'], $opt['make_table_row']) : "|||\n";
	}
	function error($opt)
	{
		$this->error['msg'] = 'error';
		$this->error['body'] = '<h2>setting does not get a check</h2>';
		$this->error['body'] .= '<br /><br /><a href="' . get_script_uri() . '?'
		 . rawurlencode($opt['refer']) . '#TableEdit2TableNumber' . $opt['table_num']
		 . '">back</a>';
	}
	function make_table($col,$row)
	{
		$table = '';
		for ($y = 0;$y < $row;$y++) {
			for ($x = 0;$x <= $col;$x++) {
				$table .= '|';
			}
			$table .= "\n";
		}
		return $table;
	}
	function plugin_set_opt($chk)
	{
		if ( isset($chk) && $chk != '') {
			return $this->set_opt . $chk . "\n"
				. $this->table_data;
		} else {
			return $this->set_opt . "{{{\n"
				. $this->table_data . "}}}\n";
		}
	}
}
class TableEdit2Edit
{
	var $opt = array();
	var $line_count_td = 1;
	var $notimestamp = FALSE;
	var $chose;

	function TableEdit2Edit($post_opt)
	{
		$this->opt = $post_opt;
	}
	function td_edit($match_t)
	{
		$end_cell = count ($match_t);
		$source_s = ($this->chose === 1) ? '|' : ''; //$this->chose_f_table('prefix','');
		for ($x = 0;$x < $end_cell;$x++){
			if ($x == ($this->opt['cell_count'] - 1)){
				if (isset($this->opt['delete'])){
					$source_s .= '';
				} else if ($this->opt['edit_mod'] == 'td'){
					$source_s .= $this->chose_f_table('chose', $this->t_quote( $match_t[$x] ) )
						. $this->chose_f_table('suffix', '');
					$this->notimestamp = TRUE;
				} else {
					if ($this->opt['add_show']) $source_s .= $this->chose_f_table('chose', $match_t[$x]);
					$source_s .= $this->textarea_br($this->line_count_td);
				}
			} else {
				$source_s .= $this->chose_f_table('chose', $this->t_quote( $match_t[$x] ));
			}
		}
		$this->line_count_td++;
		return $source_s;
	}
	function tr_edit($args_line, $match_t, $last_character)
	{
		if($this->opt['edit_mod'] == 'tr') $source_s = $args_line;	//tr_add
		$source_s .= (isset($this->opt['delete']) || $this->chose === 2) ? '' : '|';
		if($this->opt['edit_mod'] == 't_edit'){				//t_edit
			if(isset($this->opt['write'])){
				for ($i = 1;$i < $this->opt['cell_count'];$i++){
					$source_s .= $this->textarea_br($i);
				}
				$source_s .= ($this->opt['add_show']) ? "\n" : $last_character . "\n";
			}
		} else if($this->opt['edit_mod'] == 'tr'){			//tr	tr_add
			$this->notimestamp = TRUE;
			foreach ($match_t as $cell){
				$source_s .= $this->chose_f_table('prefix','');
			}
			$source_s .= "\n";
		}
		return $source_s;
		
	}

// write in table : \n > &br;	| > &#124;
	function textarea_br($cell_number)
	{
		$w_cell = $this->opt['cell' . $cell_number];
		$w_cell = str_replace("\n", '&br;', str_replace("\r",'',$w_cell));
		$w_cell = str_replace('|', '&#124;', $w_cell);

		return $this->chose_f_table('chose', $this->t_quote($w_cell));
	}
	function t_quote($data)
	{
		if ( $this->chose     === 2 &&
			strpos($data,',') !== false &&
			strpos($data,'"') === false )
		{
			$data = '"' . $data . '"';
		}
		return $data;
	}
// "," or "|" chose
	function chose_f_table($mode, $data)
	{

		$set_f = array();
		$set_f[1]  = '|';
		$set_f[2]  = ',';
		if ( $mode === 'chose' ) {
			if ( $this->chose === 2 ) $mode = 'prefix';
			if ( $this->chose === 1 ) $mode = 'suffix';
		}
		if ( $mode === 'prefix' ) return $set_f[$this->chose] . $data;
		if ( $mode === 'suffix' ) return $data . $set_f[$this->chose];
		return $set_f[$this->chose] . $data . $set_f[$this->chose];

	}
	function chk_csv_source($args, $key)
	{
		$line_key = $key;
		do {
			$t_str = $args[$line_key]{0};
			if ( $t_str == "}" || $t_str == "\n") break;
			if ( $t_str == "," ) {
				$this->chose = 2;
				break;
			}
			$line_key++;
		} while (isset($args[$line_key]));
	}
}
class TableEdit2Show extends TableEdit2Form
{
	var	$title = '';
	var $opt = array();
//	var $t_type = PLUGIN_TABLE_EDIT2_TEXTAREA;
	var $t_type;
	var $text_type = array();
	var $table_header = array();
	var $page;
	var $chk_table_sub_first_line;
	var $td_title;
	var $line_count;
	var $cells;
	var $input_bg_color = array();
	var $add_title;

	function TableEdit2Show( $post_opt, $page )
	{
		$this->t_type = PLUGIN_TABLE_EDIT2_TEXTAREA ;
		$this->opt = $post_opt;
		$this->page = $page;
		$this->add_title = ($this->opt['add_show']) ? _(' [Add Mode]'): '';
	}
	function text_type($pi_opt)
	{
		if(preg_match('/(.?)textarea(.?)/i',$pi_opt,$textarea_chk))
			if($textarea_chk[1] != '=' && $textarea_chk[2] != '=' && $textarea_chk[1] != '|' && $textarea_chk[2] != '|')
				$this->t_type = 'textarea';
		if(preg_match('/form=(.*?)[,\)]/i',$pi_opt,$match_form))
			$this->text_type = explode("|", $match_form[1]);
		if(preg_match('/input_bg_color=(.*?)[,\)]/i',$pi_opt,$match_color))
			$this->input_bg_color = explode("|", $match_color[1]);
		if(strstr($pi_opt, 'no_null=1')) $this->no_null = 1;
	}
	function text_type_textarea($count)
	{
		for ( $x = 0 ; $x <= $count ; $x++ ) {
			if (! isset( $this->text_type[$x] ) )
				$this->text_type[$x] = 'textarea';
		}
	}
	function table_header($cells)
	{
		$cell_count = 1;
		foreach ($cells as $cell){
			$this->table_header[$cell_count] = $cell;
			$cell_count++;
		}
	}
	function show_mod($cells_t)
	{
		if($this->opt['edit_mod'] == 'show'){				//show
			$this->title = 'table_edit2 ' . $this->page;
			return $this->table_edit_form( $this->line_count, $cells_t);

		} else if($this->opt['edit_mod'] == 'tdshow'){			//tdshow
			$this->cells[$this->line_count] = $cells_t;
			$column_num = $this->opt['cell_count'] - 1;
			$x_count = 1;
			for ( $x = $this->chk_table_sub_first_line ;$x <= $this->line_count ; $x++){
				$column_cell[$x] = $this->cells[$x][$column_num];
				$this->table_header[$x_count] = $this->td_title[$x];
				$x_count++;
			}
			$this->text_type = array_fill(0, $x_count, $this->text_type[$this->opt['cell_count'] - 1]);	//form 06.11.13
			$this->title = 'table_edit2 ' . $this->page;
			return $this->table_edit_form( $this->opt['cell_count'], $column_cell);
		}
	}
	//table edit form
	function table_edit_form( $edit_count, $edit_cell )
	{

	$helptags = (function_exists('edit_form_assistant')) ? edit_form_assistant() : '';	//06.11.15 assistant
	$helpdiv  = ($helptags == '') ? '' : ' onmouseup="pukiwiki_pos()" onkeyup="pukiwiki_pos()"';
	$s_table_ok         = _('OK');
	$s_table_cancel     = _('cancel');
	$s_table_delete     = _('delete');
	$s_table_time_stamp = _('no time stamp');
	$s_table_title      = _('table');
	$s_table_line       = _('line');
	$s_table_column     = _('column line');
	$script_uri         = get_script_uri();
	$table_num          = $this->opt['table_num'];
	$table_sub_num      = $this->opt['table_sub_num'];
	$digest             = md5(get_source($this->page, TRUE, TRUE));

	if ($this->opt['edit_mod'] == 'show'){
		$line_name = " $s_table_line";
		$edit_mod  = 't_edit';
		$x_count   = 'line_count';
		$y_count   = 'cell_count';
	} else if ($this->opt['edit_mod'] == 'tdshow'){
		$line_name = " (sub number=" . $table_sub_num . ") $s_table_column";
		$edit_mod  = 't_edit_td';
		$x_count   = 'cell_count';
		$y_count   = 'line_count';
	}

	$this->bgcolor($this->input_bg_color);

	$body = <<<EOD
<h3>$s_table_title=$table_num$line_name=$edit_count{$this->add_title}</h3>
<form action="$script_uri" method="post">
 <div class="commentform"$helpdiv>
  <table cellspacing="0" cellpadding="2" class="style_table">
EOD;

	$cell_count = 1;
	foreach ($edit_cell as $cell){

		if ($this->opt['add_show']) $cell = '';
	
		$body .= '  <tr><td>' . $this->table_header[$cell_count] . '(' . $cell_count . ')</td><td class="style_td">';

		if (isset($this->text_type[$cell_count - 1])) {
			preg_match('/^([a-z]+)(=|)(.*)$/', $this->text_type[$cell_count - 1], $t_data);
			$pos = strpos($t_data[1], 'text');
			if ($t_data[2] === '=' && $t_data[3] !== '' && $cell === '' && $pos !== false) $cell = $t_data[3];
		}

		$input_text = $this->text(
			'cell' . $cell_count,
			htmlspecialchars($cell),
			PLUGIN_TABLE_EDIT2_TEXT_SIZE) . '</td></tr>' . "\n";
		
		if (isset($this->text_type[$cell_count - 1])) {
			switch ($t_data[1]) {

			case  'textarea':
				$cell = htmlspecialchars(preg_replace(array("[&br;]"), array("\n"), $cell));	// textarea
				$body .= $this->textarea(
					'cell' . $cell_count,
					 $cell,
					 PLUGIN_TABLE_EDIT2_TEXTAREA_ROWS_LINE,
					 PLUGIN_TABLE_EDIT2_TEXTAREA_COLS_SIZE) . "\n";
				break;

			case 'select':
				$select_data = explode("_", $t_data[3]);
				$this->select_chk = 0;
				$select = $this->select($select_data, 'cell' . $cell_count, $cell);
				$body .= ( $this->select_chk ) ? $select : $input_text;
				break;

			case 'radio':
				$radio_data = explode("_", $t_data[3]);
				$this->radio_chk = 0;
				$radio = $this->radio($radio_data, 'cell' . $cell_count, $cell);
				$body .= ( $this->radio_chk ) ? $radio : $input_text;
				break;

			default:
				$body .= $input_text;
			}
		} else {
			$body .= $input_text;
		}
	$cell_count++;
	}
	
	$delete_or_addshow = ($this->opt['add_show']) ?
		$this->f_input('hidden','add_show', 1) :
		$this->f_input('submit','delete',$s_table_delete) ;
	
	$body .= <<<EOD
  <tr><td  colspan="2">
   <input type="hidden" name="plugin"        value="table_edit2" />
   <input type="hidden" name="refer"         value="{$this->page}" />
   <input type="hidden" name="table_num"     value="$table_num" />
   <input type="hidden" name="table_sub_num" value="$table_sub_num" />
   <input type="hidden" name="$x_count"      value="$edit_count" />
   <input type="hidden" name="$y_count"      value="$cell_count" />
   <input type="hidden" name="edit_mod"      value="$edit_mod" />
   <input type="hidden" name="digest"        value="$digest" />
   <input type="submit" name="write"         value="$s_table_ok" />
   <input type="submit" name="cancel"        value="$s_table_cancel" />
   $delete_or_addshow $s_table_time_stamp
   <input type="checkbox" name="notimestamp" />
   $helptags
  </td></tr>
 </table>
 </div>
</form>
EOD;
	return $body;
	}
}
function plugin_table_edit2_spam($hint)
{
	if (isset($hint) && $hint != '') {
		if (PKWK_ENCODING_HINT != $hint) return TRUE;
	} else {
		if (PKWK_ENCODING_HINT != '') return TRUE;
	}
}
function plugin_table_edit2_honeypot()
{
	// Logging for SPAM Report
	honeypot_write();

	// Same as "Cancel" action
	return array('msg'=>'', 'body'=>''); // Do nothing
}


class TableEdit2CsvAction
{
	var $error;
	var $csv_data;
	var $script_uri;
	var $filename;
	var $logname;
	
	function csv_import($opt)
	{
//		$script_uri = get_script_uri();
		$page = isset($opt['refer']) ? $opt['refer'] : '';

		$file = $_FILES['table_edit2_csv_file'];
	
		$file_error = $this->file_error($file);
		if ($this->error) return $file_error;
	

		$obj = & new TableEdit2CsvConversion($page, $file, $opt['charset_in'], 'import');

		$csv_source = array();
		$fp = @fopen($file['tmp_name'], 'r');
		if ($fp) {
			@flock($fp, LOCK_SH);
			$csv_source = str_replace(array("\r\n","\n","\r"), '&br;',file($file['tmp_name']));	//"\r\n"
			@fclose($fp);
		}

		unlink($file['tmp_name']);

		$table_source = $obj->convert_csv_fields($csv_source, "\n", $opt['charset_in']);

		$this->csv_data = join('', $table_source);

	}
	function csv_export($opt, $csv_source)
	{
		$this->script_uri = get_script_uri();
		$page = isset($opt['refer']) ? $opt['refer'] : '';

		$opt_name = '';
		switch ( $opt['w_quote'] ) {
			case 'moji':
				$opt_name = '_str';
				break;
			case 'retu':
				$opt_name = str_replace( ',', '', $opt['column_w_q']);
				break;
		}
		
		$opt_name = $opt['charset_out'] . $opt['end_of_line'] . $opt_name;
		$file['name'] = 'table_data' . $opt['table_num'] . '_' . $opt_name . '.csv';
	
		$obj = & new TableEdit2CsvConversion($page, $file, SOURCE_ENCODING, 'export');

		$obj->w_quote =  $opt['w_quote'];
		if ( strpos($opt['column_w_q'], ',') === FALSE ) {
			$obj->column_w_q[] = $opt['column_w_q'];
		} else {
			$obj->column_w_q = explode(',', $opt['column_w_q']);
		}

		$csv_file = $obj->convert_csv_fields($csv_source, $obj->end_of_line($opt['end_of_line']), SOURCE_ENCODING);

		$fp = @fopen($obj->filename, 'w');
		if ($fp) {
			@flock($fp, LOCK_EX);
			$csv_file = join('', $csv_file);
			stream_set_write_buffer($fp, 0);
			fwrite($fp, $obj->mb_out_c($csv_file, $opt['charset_out']));
			@flock($fp, LOCK_UN);
			@fclose($fp);
		}

		$obj->getstatus();
		$obj->putstatus();

		$this->logname = $obj->logname;
		$this->filename = $obj->filename;
		
		return $this->export_d($page, $opt['table_num'], $file['name']);

		header('Location: ' . $this->script_uri . '?' . rawurlencode($page));
		exit;

	}
	function import_data_set($opt, $chk)
	{
		$opt = str_replace('csv_select=import,', '', $opt);
		$opt = '#table_edit2(' . $opt . ')';
		if ( isset($chk) && $chk != '') {
			return $opt . $chk . "\n"
				. $this->csv_data;
		} else {
			return $opt . "{{{\n"
				. $this->csv_data . "\n}}}\n";
		}
	}
	function set_csv_opt($opt, $chk, $csv_opt)
	{
		$opt = '#table_edit2(csv_select=' . $csv_opt . ',' . $opt . ')';
		return $opt . $chk . "\n";
	}
	function cancel($opt, $chk, $delete)
	{
		$opt = str_replace('csv_select=' . $delete . ',', '', $opt);
		$opt = '#table_edit2(' . $opt . ')';
		return $opt . $chk . "\n";
	}
	function export_d($page, $table_num, $file)
	{
	$download = _('download');
	$back = _('back');
	$ref = (exist_plugin_inline('ref')) ? do_plugin_inline('ref', $page . '/' . $file ) : _('ref plugin is not found');
	
	$body = <<<EOD
<h3>$page table number $table_num</h3>
<br />
<br />
<b>$download</b>$ref
<br />
<br />
<form enctype="multipart/form-data" action="$this->script_uri" method="post">
 <div>
  <input type="hidden" name="plugin"     value="table_edit2" />
  <input type="hidden" name="refer"      value="$page" />
  <input type="hidden" name="table_num"  value="$table_num" />
  <input type="hidden" name="file_name"  value="$file" />
  <input type="submit" name="csv_back"   value="$back" />
 </div>
</form>
EOD;

	return array(
		'msg'  => _('export'),
		'body' => $body
		);
	}
	function file_error($file)
	{
		$this->error = 1;
		if(! is_uploaded_file($file['tmp_name']) )
			return array(
				'msg'  => _('cannot upload'),
				'body' => _('Please upload it by a POST.')
			);
		switch ( $file['error'] ) {
			case UPLOAD_ERR_INI_SIZE:	//1
				return array(
					'msg'  => _('cannot upload'),
					'body' => _('The uploaded file exceeds the upload_max_filesize directive in php.ini.')
				);
				break;
			case UPLOAD_ERR_FORM_SIZE:	//2
				return array(
					'msg'  => _('cannot upload'),
					'body' => _('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.')
				);
				break;
			case UPLOAD_ERR_PARTIAL:	//3
				return array(
				'msg'  => _('cannot upload'),
				'body' => _('The uploaded file was only partially uploaded.')
				);
				break;
			case UPLOAD_ERR_NO_FILE:	//4
				return array(
					'msg'  => _('cannot upload'),
					'body' => _('No file was uploaded.')
				);
				break;
			case UPLOAD_ERR_NO_TMP_DIR:	//6 PHP 4.3.10 - PHP 5.0.3
				return array(
					'msg'  => _('cannot upload'),
					'body' => _('Missing a temporary folder.')
				);
				break;
		}
	
		if (function_exists('mime_content_type')) 
			if(! mime_content_type($file['tmp_name']) == 'text/plain')
				return array(
					'msg'  => _('cannot upload'),
					'body' => _('A file unlike text was uploaded.')
				);
		
		$this->error = 0;

	}

}
class TableEdit2CsvConversion
{
	var $csv_mod;
	var $page, $file, $basename, $filename, $logname, $exist;
	var $time = 0;
	var $size = 0;
	var $time_str = '';
	var $size_str = '';
	var $status = array('count'=>array(0));
	var $type = '';
	var $csv_fields = array();
	var $char;
	var $w_quote = 'no';
	var $column_w_q = array();
	var $number = 0;
	var $num_chk = 'No';
	var $num_memo = array();
	var $glue = array();
	
	var $join_line = '';
	var $list_csv = array();

	function TableEdit2CsvConversion($page, $file, $csv_char = 'SJIS', $csv_mod)
	{
		$this->csv_mod = $csv_mod;
		if ($csv_mod == 'import') {
			$this->glue = array(',','|');
		} else if ($csv_mod == 'export') {
			$this->glue = array('|',',');
		}
		$this->page = $page;
		$this->file = basename($file['name']);

		$this->char = $csv_char;

		$this->basename = UPLOAD_DIR . encode($page) . '_' . encode($this->file);
		$this->filename = $this->basename;
		$this->logname  = $this->basename . '.log';
		$this->exist    = file_exists($this->filename);
		$this->time     = $this->exist ? filemtime($this->filename) - LOCALZONE : 0;
	}
	//get file status
	function getstatus()
	{
		if (! $this->exist) return FALSE;

		// get log
		$this->time_str = get_date('Y/m/d H:i:s', $this->time);
		$this->size     = filesize($this->filename);
		$this->size_str = sprintf('%01.1f', round($this->size/1024, 1)) . 'KB';
		$this->type     = 'text/plain';

		return TRUE;
	}

	// save status
	function putstatus()
	{
		$this->status['count'] = join(',', $this->status['count']);
		$fp = fopen($this->logname, 'wb') or
			die_message('cannot write ' . $this->logname);
		set_file_buffer($fp, 0);
		flock($fp, LOCK_EX);
		rewind($fp);
		foreach ($this->status as $key=>$value) {
			fwrite($fp, $value . "\n");
		}
		flock($fp, LOCK_UN);
		fclose($fp);
	}
	function end_of_line($end_of_line)
	{
		switch ( $end_of_line ) {
			case 'win' : return "\r\n";
			case 'unx' : return "\n";
			case 'mac' : return "\r";
		}
	}
	function charset($char_set)
	{
		switch ( $char_set ) {
			case 'sjis' : return 'SJIS';
			case 'utf8' : return 'UTF-8';
			case 'eucjp' : return 'EUC-JP';
		}
	}
	function convert_csv_fields($csvlines, $end_of_line, $charset)
	{
		$charset = isset($charset) ? $charset : $this->char;
		$fields = array();
		$y = 0;
		foreach ( $csvlines as $line ) {
			if ( $this->csv_mod == 'export' ) {
				$x = 0;
				$fields = explode($this->glue[0], preg_replace('|\&br\;$|','',$line));
				foreach ( $fields as $field ) {
				
					$field = str_replace( '"', '""', $field);
					$this->csv_fields[$y][$x] = preg_match('/([",]|&br;)/', $field, $match) ? '"' . $field . '"' : $field;
					if (! isset ( $match[1] ) )
						$this->csv_fields[$y][$x] = $this->w_quote_f($this->csv_fields[$y][$x], $x);
					$x++;
				}
				$this->list_csv[$y] = preg_replace(
					'|\&br\;|',
					$end_of_line,
					$this->t_format(join($this->glue[1], $this->csv_fields[$y])) ) . $end_of_line;
				$y++;
			} else if ( $this->csv_mod == 'import' ) {
				$this->csv_import_cv($line, $charset, $end_of_line);
			}
		}
		return $this->list_csv;
	}
	function csv_import_cv($line, $charset, $end_of_line)
	{
		$line = $this->mb_in_c( $line, $charset);
		$this->join_line .= $line;
		$chk_count = substr_count ( $this->join_line, '"');
		if (!( $chk_count % 2 )) {
			$csv_fields = array();
			$join_f = '';
			$fields = explode(',', $this->join_line);
			foreach ( $fields as $field ) {
				$join_f .= preg_replace('|&br;$|','',$field);
				$chk_count_f = substr_count ( $join_f, '"');
				if (!( $chk_count_f % 2 )) {
					$csv_f = preg_match('/^"(.*?)"$/', $join_f, $match) ? $match[1] : $join_f;
					$csv_fields[] = str_replace( '""', '"', $csv_f);
					$join_f = '';
				} else {
					$join_f .= ',';
				}
			}
			$this->join_line = '';
			$this->list_csv[] = $this->t_format(join($this->glue[1], $csv_fields) ) . $end_of_line;
		}
	}
	function t_format($data)
	{
		if ($this->csv_mod == 'import') {
			return '|' . $data . '|';
		} else if ($this->csv_mod == 'export') {
			return $data;
		}
	}
	function w_quote_f($data, $x)	//w quote
	{
		if ( $this->w_quote == 'moji' ) {
			if (! is_numeric($data) ) {
				return '"' . $data . '"';
			} else if ( preg_match('|\.|',$data) ) {
				return '"' . $data . '"';
			}
		} else if ( $this->w_quote == 'retu' ) {
			if ( in_array($x, $this->column_w_q) )
				return '"' . $data . '"';
		}
		return $data;
	}
	function mb_out_c($data, $out_char)		//utf8 -> xxxx
	{
		$out_char = isset($out_char) ? $out_char : $this->char;
		return mb_convert_encoding($data, $out_char, SOURCE_ENCODING);
	}
	function mb_in_c($data, $in_char)		//xxxx -> utf-8
	{
		$in_char = isset($in_char) ? $in_char : $this->char;
		return mb_convert_encoding($data, SOURCE_ENCODING, $in_char);
	}
}

?>
