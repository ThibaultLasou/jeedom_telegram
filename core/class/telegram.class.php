<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class telegram extends eqLogic {

	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	public static function health() {
		$https = strpos(network::getNetworkAccess('external'), 'https') !== false;
		$return[] = array(
			'test' => __('HTTPS', __FILE__),
			'result' => ($https) ? __('OK', __FILE__) : __('NOK', __FILE__),
			'advice' => ($https) ? '' : __('Votre Jeedom ne permet pas le fonctionnement de Telegram sans HTTPS', __FILE__),
			'state' => $https,
		);
		return $return;
	}

	/*     * *********************Methode d'instance************************* */

	public function postSave() {
		$text = $this->getCmd(null, 'text');
		if (!is_object($text)) {
			$text = new telegramCmd();
			$text->setLogicalId('text');
			$text->setIsVisible(0);
			$text->setName(__('Message', __FILE__));
		}
		$text->setType('info');
		$text->setSubType('string');
		$text->setEqLogic_id($this->getId());
		$text->save();

		$sender = $this->getCmd(null, 'sender');
		if (!is_object($sender)) {
			$sender = new telegramCmd();
			$sender->setLogicalId('sender');
			$sender->setIsVisible(0);
			$sender->setName(__('Expediteur', __FILE__));
		}
		$sender->setType('info');
		$sender->setSubType('string');
		$sender->setEqLogic_id($this->getId());
		$sender->save();

		$ask_sender = $this->getCmd(null, 'ask_sender');
		if (!is_object($ask_sender)) {
			$ask_sender = new telegramCmd();
			$ask_sender->setLogicalId('ask_sender');
			$ask_sender->setIsVisible(0);
			$ask_sender->setName(__('Dernier expediteur ASK', __FILE__));
		}
		$ask_sender->setType('info');
		$ask_sender->setSubType('string');
		$ask_sender->setEqLogic_id($this->getId());
		$ask_sender->save();

		$sender = $this->getCmd(null, 'chat');
		if (!is_object($sender)) {
			$sender = new telegramCmd();
			$sender->setLogicalId('chat');
			$sender->setIsVisible(0);
			$sender->setName(__('Chat', __FILE__));
		}
		$sender->setType('info');
		$sender->setSubType('string');
		$sender->setEqLogic_id($this->getId());
		$sender->save();

		$cmd = $this->getCmd(null, 'lastaskuser');
		if (!is_object($cmd)) {
			$cmd = new telegramCmd();
			$cmd->setLogicalId('lastaskuser');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Dernier utilisateur ASK', __FILE__));
			$cmd->setType('action');
			$cmd->setConfiguration('chatid', 'Dernier utilisateur ASK');
			$cmd->setConfiguration('firstname', 'Dernier utilisateur ASK');
			$cmd->setConfiguration('username', 'Dernier utilisateur ASK');
			$cmd->setSubType('message');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setDisplay('title_placeholder', __('Options', __FILE__));
			$cmd->setDisplay('message_placeholder', __('Message', __FILE__));
			$cmd->save();
		}

		$cmd = $this->getCmd(null, 'lastuser');
		if (!is_object($cmd)) {
			$cmd = new telegramCmd();
			$cmd->setLogicalId('lastuser');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Dernier utilisateur', __FILE__));
			$cmd->setType('action');
			$cmd->setConfiguration('chatid', 'Dernier utilisateur');
			$cmd->setConfiguration('firstname', 'Dernier utilisateur');
			$cmd->setConfiguration('username', 'Dernier utilisateur');
			$cmd->setSubType('message');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setDisplay('title_placeholder', __('Options', __FILE__));
			$cmd->setDisplay('message_placeholder', __('Message', __FILE__));
			$cmd->save();
		}

		$cmd = $this->getCmd(null, 'alluser');
		if (!is_object($cmd)) {
			$cmd = new telegramCmd();
			$cmd->setLogicalId('alluser');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Tous', __FILE__));
			$cmd->setType('action');
			$cmd->setConfiguration('chatid', 'Tous les utilisateurs');
			$cmd->setConfiguration('firstname', 'Tous les utilisateurs');
			$cmd->setConfiguration('username', 'Tous les utilisateurs');
			$cmd->setSubType('message');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setDisplay('title_placeholder', __('Options', __FILE__));
			$cmd->setDisplay('message_placeholder', __('Message', __FILE__));
			$cmd->save();
		}
		$url = network::getNetworkAccess('external') . '/plugins/telegram/core/api/jeeTelegram.php?apikey=' . jeedom::getApiKey('telegram') . '&id=' . $this->getId();
		$token = trim($this->getConfiguration('bot_token'));
		$request_http = new com_http('https://api.telegram.org/bot' . $token . '/setWebhook');
		log::add('telegram', 'debug', $url);
		$post = array(
			'url' => $url,
		);
		$request_http->setPost($post);
		try {
			$result = $request_http->exec(60, 1);
		} catch (Exception $e) {
		}
		log::add('telegram', 'debug', $result);
	}
}

class telegramCmd extends cmd {

	public function preSave() {
		if ($this->getSubtype() == 'message') {
			$this->setDisplay('title_disable', 0);
			$this->setDisplay('message_disable', 0);
			//$this->setDisplay('title_placeholder', __('Options', __FILE__));
			//$this->setDisplay('message_placeholder', __('Message', __FILE__));
		}
	}

	public function sendTelegram($_url, $_type, $_to, $_data) {
		foreach ($_to as $chatid) {
			$_data['chat_id'] = $chatid;
			$request_http = new com_http($_url);
			if ($_type == 'file') {
				$request_http->setHeader(array("Content-Type:multipart/form-data"));
			}
			$request_http->setPost($_data);
			$request_http->setNoReportError(true);
			log::add('telegram', 'debug', 'Call url ' . $_url . ' with option ' . print_r($_data, true));
			$output = $request_http->exec(90);
			log::add('telegram', 'debug', 'Result : ' . $output);
			if (!is_json($output)) {
				throw new Exception(__('Erreur lors de l\'envoi telegram : ', __FILE__) . $output);
			}
			$result = json_decode($output, true);
			if (!$result['ok']) {
				throw new Exception(__('Erreur lors de l\'envoi telegram : ', __FILE__) . $output);
			}
		}
	}

	public function execute($_options = array()) {
		if ($this->getType() == 'info') {
			return;
		}

		$max_photo_size			= 10000000;
		$max_other_file_size 	= 50000000;
		$max_media_by_group		= 10;

		$data = array();
		$options = array();
		if (isset($_options['title'])) {
			$options = arg2array($_options['title']);
		}
		$eqLogic = $this->getEqLogic();
		$to = array();
		if ($this->getLogicalId() == 'alluser') {
			foreach ($eqLogic->getCmd('action') as $cmd) {
				if (!in_array($cmd->getLogicalId(), ['alluser', 'lastaskuser', 'lastuser'])) {
					$to[] = $cmd->getConfiguration('chatid');
				}
			}
		} elseif ($this->getLogicalId() == 'lastaskuser') {
			$to[] = $eqLogic->getCmd(null, 'ask_sender')->execCmd();
		} elseif ($this->getLogicalId() == 'lastuser') {
			$to[] = $eqLogic->getCmd(null, 'sender')->execCmd();
		} else {
			$to[] = $this->getConfiguration('chatid');
		}
		$request_http = "https://api.telegram.org/bot" . trim($eqLogic->getConfiguration('bot_token'));
		$data['disable_notification'] = $eqLogic->getConfiguration('disable_notify', 0);
		if (isset($options['disable_notification'])) {
			$data['disable_notification'] = $options['disable_notification'];
		}
		$data['parse_mode'] = $eqLogic->getConfiguration('parse_mode', 'HTML');
		if (isset($options['parse_mode'])) {
			$data['parse_mode'] = $options['parse_mode'];
		}
		$data = array_merge($data, $options);

		if (isset($_options['answer'])) {
			$data['disable_notification'] = 0;
			if (strpos($_options['answer'][0], 'answers_per_line') !== false) {
				$data['reply_markup'] = json_encode(array(
					'keyboard' => array_chunk(array_splice($_options['answer'], 1), str_replace("answers_per_line=", "", $_options['answer'][0])),
					'one_time_keyboard' => true,
					'resize_keyboard' => true,
				));
			} else {
				$data['reply_markup'] = json_encode(array(
					'keyboard' => array($_options['answer']),
					'one_time_keyboard' => true,
					'resize_keyboard' => true,
				));
			}
		}
		/*using inline keyboard, not really good for usage
            $inline = array();
            foreach ($_options['answer'] as $value) {
                $inline[] = array('text' => $value, 'callback_data' => $value);
            }
            $data['reply_markup'] = json_encode(array(
            'inline_keyboard' => array($inline)
        ));*/

		if (isset($options['empty'])) {
			unset($data['empty']);
			$data['text'] = "Délai dépassé ou quelqu'un a répondu";
			if ($_options['message'] != '') {
				$data['text'] = $_options['message'];
			}
			$data['reply_markup'] = json_encode(array(
				'remove_keyboard' => true,
			));
			$url = $request_http . "/sendMessage";
			$this->sendTelegram($url, 'message', $to, $data);
			return;
		}

		if (isset($options['location'])) {
			unset($data['location']);
			if (strrpos($options['location'], '#') !== false) {
				$geolocval = geotravCmd::byEqLogicIdAndLogicalId(str_replace('#', '', $options['location']), 'location:coordinate')->execCmd();
			} else {
				$geolocval = $options['location'];
			}
			$coordinate = explode(',', $geolocval);
			$data['latitude'] = $coordinate[0];
			$data['longitude'] = $coordinate[1];
			$url = $request_http . "/sendLocation";
			$this->sendTelegram($url, 'message', $to, $data);
		}

		if (isset($options['tts'])) {
			unset($data['tts']);
			if (is_file(realpath($options['tts']))) {
				$data['voice'] = new CURLFile(realpath($options['tts']));
			} else {
				exec("pico2wave -l fr-FR -w /tmp/voice.wav \"" . $options['tts'] . "\"");
				exec("opusenc --bitrate 64 /tmp/voice.wav /tmp/voice.ogg");
				$data['voice'] = new CURLFile(realpath('/tmp/voice.ogg'));
			}
			$url = $request_http . "/sendVoice";
			$this->sendTelegram($url, 'file', $to, $data);
		}

		if (isset($options['file'])) {
			unset($data['file']);
			$_options['files'] = explode(',', $options['file']);
		}
		
		if (isset($options['url'])) {
			unset($data['url']);
			foreach(explode(',', $options['url']) as $file) {
				$save = '/tmp/' . basename($file);
				log::add('telegram', 'debug', 'URL save : ' . $save);
				unlink($save);
				$cmd = 'curl ' . $file . ' -o ' . $save;
				shell_exec($cmd);
				$_options['files'][] = $save;
			}
		}
		
		if (isset($options['snapshot'])) {
			unset($data['snapshot']);
			$save = '/tmp/telegram_' . $this->getId() . '.png';
			unlink($save);
			$cmd = 'curl ' . (isset($options['curl_opts']) ? trim($options['curl_opts'], "\"") : '') . ' ' . $options['snapshot'] . ' -o ' . $save;
			shell_exec($cmd);
			$_options['files'][] = $save;
		}
		
		if (isset($options['rtspSnapshot'])) {
			unset($data['rtsp']);
			$save = '/tmp/telegram_' . $this->getId() . '.png';
			unlink($save);
			$cmd = 'ffmpeg -rtsp_transport tcp -loglevel fatal -i "' . $options['rtspSnapshot'] . '" -f image2 -vf fps=fps=1 ' . $save;
			shell_exec($cmd);
			$_options['files'][] = $save;
		}
		
		if (isset($options['rtspVideo'])) {
			unset($data['rtsp']);
			$save = '/tmp/telegram_' . $this->getId() . '.mp4';
			unlink($save);
			$cmd = 'ffmpeg -rtsp_transport tcp -loglevel fatal -i "' . $options['rtspVideo'] . '" -c:v copy -bsf:a aac_adtstoasc -y -t 10 -movflags faststart ' . $save;
			shell_exec($cmd);
			$_options['files'][] = $save;
		}

		if (!isset($_options['files']) && $_options['message'] != '') {
			$data['text'] = trim($_options['message']);
			$url = $request_http . "/sendMessage";
			$this->sendTelegram($url, 'message', $to, $data);
		}

		if (isset($_options['files']) && is_array($_options['files'])) {
		$i=0;
		$file_type = array();
			foreach ($_options['files'] as $file) {
				if (trim($file) == '') {
					continue;
				}

				$ext = pathinfo($file, PATHINFO_EXTENSION);
				if (in_array($ext, array('mp4', 'gif')) && filesize($file) < $max_other_file_size)  {
					$file_type[$file] = 'animation';
				}
				else if (in_array($ext, array('jpeg', 'jpg', 'png')) && filesize($file) < $max_photo_size) {
					$file_type[$file] = 'photo';
				} else if (in_array($ext, array('ogg', 'mp3')) && filesize($file) < $max_other_file_size) {
					$file_type[$file] = 'audio';
				} else if (in_array($ext, array('avi', 'mpeg', 'mpg', 'mkv', 'mp4', 'mpe')) < $max_other_file_size) {
					$file_type[$file] = 'video';
				} else {
					$file_type[$file] = 'document';
				}
			}

			$text = ($_options['message'] == '') ? pathinfo($file, PATHINFO_FILENAME) : $_options['message'];

			$types = array_values(array_unique($file_type));
			if(count($types) == 1 && $types[0] !== 'animation')
			{
				$url = $request_http . '/sendMediaGroup';
				$media_array = array();
				$i=0;
				foreach ($_options['files'] as $file) {
					$media = array();
					$media['type'] = $types[0];
					$media['media'] = 'attach://' . $file;
					array_push($media_array, $media);
					$data[$file] = new CURLFile(realpath($file));
					$i++;
					if($i == $max_media_by_group)
					{
						$media_array[0]['caption'] = $text;
						$data['media'] = json_encode($media_array);
						$this->sendTelegram($url, 'file', $to, $data);
						$i=0;
					}
				}
			}
			else
			{
				foreach ($_options['files'] as $file) {
					$data[$file_type[$file]] = new CURLFile(realpath($file));
					$data['caption'] = $text;
					switch($file_type[$file])
					{
						case 'animation':
							$url = $request_http . '/sendAnimation';
							break;
						case 'photo':
							$url = $request_http . '/sendPhoto';
							break;
						case 'audio':
							$url = $request_http . '/sendAudio';
							break;
						case 'video':
							$url = $request_http . '/sendVideo';
							break;
						case 'document':
							$url = $request_http . '/sendDocument';
							break;
					}
					$this->sendTelegram($url, 'file', $to, $data);
				}
			}
		}
	}
}
