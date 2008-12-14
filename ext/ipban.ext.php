<?php

// RemoveIPBanEvent {{{
class RemoveIPBanEvent extends Event {
	var $ip;

	public function RemoveIPBanEvent($ip) {
		$this->ip = $ip;
	}
}
// }}}
// AddIPBanEvent {{{
class AddIPBanEvent extends Event { 
	var $ip;
	var $reason;

	public function AddIPBanEvent($ip, $reason) {
		$this->ip = $ip;
		$this->reason = $reason;
	}
}
// }}}

class IPBan extends Extension {
// event handler {{{
	public function receive_event($event) {
		if(is_a($event, 'InitExtEvent')) {
			global $config;
			if($config->get_int("ext_ipban_version") < 1) {
				$this->install();
			}
		}

		$this->check_ip_ban();

		if(is_a($event, 'PageRequestEvent') && ($event->page == "ip_ban")) {
			global $user;
			if($user->is_admin()) {
				if($event->get_arg(0) == "add") {
					if(isset($_POST['ip']) && isset($_POST['reason'])) {
						send_event(new AddIPBanEvent($_POST['ip'], $_POST['reason']));

						global $page;
						$page->set_mode("redirect");
						$page->set_redirect(make_link("admin"));
					}
				}
				else if($event->get_arg(0) == "remove") {
					if(isset($_POST['ip'])) {
						send_event(new RemoveIPBanEvent($_POST['ip']));

						global $page;
						$page->set_mode("redirect");
						$page->set_redirect(make_link("admin"));
					}
				}
			}
		}

		if(is_a($event, 'AddIPBanEvent')) {
			$this->add_ip_ban($event->ip, $event->reason);
		}

		if(is_a($event, 'RemoveIPBanEvent')) {
			$this->remove_ip_ban($event->ip);
		}

		if(is_a($event, 'AdminBuildingEvent')) {
			global $page;
			$page->add_main_block(new Block("Edit IP Bans", $this->build_ip_bans()));
		}
	}
// }}}
// installer {{{
	protected function install() {
		global $database;
		global $config;
		$database->db->Execute("CREATE TABLE bans (
			id int(11) NOT NULL auto_increment,
			ip char(15) default NULL,
			date datetime default NULL,
			end datetime default NULL,
			reason varchar(255) default NULL,
			PRIMARY KEY (id)
		)");
		$config->set_int("ext_ipban_version", 1);
	}
// }}}
// deal with banned person {{{
	private function check_ip_ban() {
		$row = $this->get_ip_ban($_SERVER['REMOTE_ADDR']);
		if($row) {
			global $config;

			print "IP <b>{$row['ip']}</b> has been banned because of <b>{$row['reason']}</b>";

			$contact_link = $config->get_string("contact_link");
			if(!empty($contact_link)) {
				print "<p><a href='$contact_link'>Contact The Admin</a>";
			}
			exit;
		}
	}
// }}}
// database {{{
	public function get_bans() {
		// FIXME: many
		global $database;
		$bans = $database->db->GetAll("SELECT * FROM bans");
		if($bans) {return $bans;}
		else {return array();}
	}

	public function get_ip_ban($ip) {
		global $database;
		// yes, this is "? LIKE var", because ? is the thing with matching tokens
		return $database->db->GetRow("SELECT * FROM bans WHERE ? LIKE ip", array($ip));
	}

	public function add_ip_ban($ip, $reason) {
		global $database;
		$database->db->Execute(
				"INSERT INTO bans (ip, reason, date) VALUES (?, ?, now())",
				array($ip, $reason));
	}

	public function remove_ip_ban($ip) {
		global $database;
		$database->db->Execute("DELETE FROM bans WHERE ip = ?", array($ip));
	}
// }}}
// admin page HTML {{{
	private function build_ip_bans() {
		global $database;
		$h_bans = "";
		$bans = $this->get_bans();
		foreach($bans as $ban) {
			$h_bans .= "
				<tr>
					<td>{$ban['ip']}</td>
					<td>{$ban['reason']}</td>
					<td>
						<form action='".make_link("ip_ban/remove")."' method='POST'>
							<input type='hidden' name='ip' value='{$ban['ip']}'>
							<input type='submit' value='Remove'>
						</form>
					</td>
				</tr>
			";
		}
		$html = "
			<table border='1'>
				<thead><td>IP</td><td>Reason</td><td>Action</td></thead>
				$h_bans
				<tr>
					<form action='".make_link("ip_ban/add")."' method='POST'>
						<td><input type='text' name='ip'></td>
						<td><input type='text' name='reason'></td>
						<td><input type='submit' value='Ban'></td>
					</form>
				</tr>
			</table>
		";
		return $html;
	}
// }}}
}
add_event_listener(new IPBan(), 10);
?>