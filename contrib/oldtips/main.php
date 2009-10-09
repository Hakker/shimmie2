<?php
/**
 * Name: Random Tip
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Show a random line of text in the subheader space
 * Documentation:
 *  Formatting is done with bbcode
 */

class Tips extends SimpleExtension {
	public function onPostListBuilding($event) {
		global $config, $page;
		if(strlen($config->get_string("tips_text")) > 0) {
			$tips = $config->get_string("tips_text");
			$tips = preg_replace("/\n+/", "\n", $tips);
			$lines = explode("\n", $tips);
			$line = $lines[array_rand($lines)];
			$this->theme->display_tip(format_text($line));
		}
	}

	public function onSetupBuilding($event) {
		$sb = new SetupBlock("Tips");
		$sb->add_longtext_option("tips_text");
		$event->panel->add_block($sb);
	}
}
?>