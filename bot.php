<?php

error_reporting(E_ERROR);
include_once 'functions.php';

$json = file_get_contents('php://input');
$data = json_decode($json);
$v = '5.101';
$token = '';
$myid = 474370216;
$group_id = 172115950;
$secret = "aye";

function curl_post_contents_url($url, $params = NULL) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	$response = curl_exec($ch);
	curl_close($ch);
	return $response;
};
function vk_request($method, $params = NULL) {
	return json_decode(curl_post_contents_url('https://api.vk.com/method/' . $method, 'access_token=' . token . '&v=' . v . '&' . $params), true);
};
function uploadPhoto($peer_id, $photoaddress) {
	$uploadphoto = vk_request('photos.getMessagesUploadServer', 'peer_id=' . $peer_id);
	$url = $uploadphoto['response']['upload_url'];
	$post = array(
		'photo' => curl_file_create($photoaddress),
	);
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: multipart/form-data',
	));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($ch);
	curl_close($ch);
	$result = json_decode($result, true);
	$photosave[0] = $result['hash'];
	$photosave[1] = $result['photo'];
	$photosave[2] = $result['server'];
	$save_uploaded_photo = vk_request('photos.saveMessagesPhoto', 'hash=' . $photosave[0] . '&photo=' . $photosave[1] . '&server=' . $photosave[2]);
	$photoid = $save_uploaded_photo['response'][0]['id'];
	$owner_photo_id = $save_uploaded_photo['response'][0]['owner_id'];
	$access_key_photo = $save_uploaded_photo['response'][0]['access_key'];
	$myattach = 'photo' . $owner_photo_id . '_' . $photoid . '_' . $access_key_photo;
	return $myattach;
}
const token = ''; //Токен сообщества
const v = '5.101';


switch ($data->type) 
{
	case 'confirmation':
		echo "39c624a2"; //если запросили токен
		break;
	case 'message_new': //если новое сообщение
		if ($data->secret === $secret)
		{
			$text = $data->object->text;
			$low_text = mb_strtolower($text);
			$peer_id = $data->object->peer_id; 
			$from_id = $data->object->from_id;
			$chat_id = $peer_id - 2000000000;

			if (!file_exists($peer_id) && !is_dir($peer_id) and $chat_id > 0)
			{
				mkdir($peer_id);
				copy('default/settings.json', $peer_id.'/settings.json');
				copy('default/perm.json', $peer_id.'/perm.json');
				copy('default/warns.json', $peer_id.'/warns.json');
			}

			if ($from_id == $data->object->action->member_id and $data->object->action->type == "chat_kick_user")
			{
				if(db_get($peer_id.'/settings.json','KICK_LEAVE') === 1)
				{
					file_get_contents("https://api.vk.com/method/messages.removeChatUser?chat_id=".$chat_id."&member_id=".$from_id."&access_token=".$token."&v=".$v);
				}
			}

			if ($data->object->action->type == "chat_invite_user")
			{
				$member_id = $data->object->action->member_id;
				if(is_perm($peer_id,$member_id))
				{
					file_get_contents("https://api.vk.com/method/messages.removeChatUser?chat_id=".$chat_id."&member_id=".$member_id."&access_token=".$token."&v=".$v);
				}
				elseif(db_get($peer_id.'/settings.json','KICK_GROUPS') === 1 and $member_id < 0)
				{
					file_get_contents("https://api.vk.com/method/messages.removeChatUser?chat_id=".$chat_id."&member_id=".$member_id."&access_token=".$token."&v=".$v);
				}
				else
				{
					$mes = db_get($peer_id.'/settings.json','JOIN_TEXT');
					file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");
				}
			}
			//HELP
			if ($low_text == 'help')
			{
				$mes = "warns - узнать кол-во варнов<br>reg [id] - дата регистрации юзера<br>short [url] - сократить ссылку<br>unshort [url] - узнать куда ведет сокращенная ссылка<br>info [id] - инофрмация о юзере<br><br>kick [msg] - кикнуть юзера<br>warn [msg] - добавить 1 варн<br>unwarn [msg] - убрать 1 варн<br>perm [msg] - перманентный бан в беседе<br>unperm [msg] - убрать из перманентного банa<br>settings - список настроек беседы<br>set [param] [value] - изменить настройки беседы";
				file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");
			}
			//INFO
			elseif(strpos($low_text,"info ")===0)
			{
				$need_id = substr($low_text,5);
				$arr = explode(" ",$text);
				if (isset($arr[3]))
				{
					$response = json_decode(file_get_contents("https://api.vk.com/method/restore.init?v=5.83&lang=0&uid=".$arr[1]."&captcha_sid=".$arr[2]."&captcha_key=".$arr[3]));
				}
				else
				{
					$response = json_decode(file_get_contents("https://api.vk.com/method/restore.init?v=5.83&lang=0&uid=".$need_id));
				}
				if (isset($response->error) and $response->error->error_code == "14")
					{
						copy($response->error->captcha_img,"captcha.jpg");
						$mes = "Вам капча<br>Вам надо написать:<br>info ".$arr[1]." ".$response->error->captcha_sid." [текст с картинки]";
						$myattach = uploadPhoto($peer_id,__DIR__ .'/captcha.jpg');
						file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&attachment=".$myattach."&access_token=".$token."&v=".$v."&random_id=0");
					}
				elseif ($response->response[1]->user[0]->id == "")
				{
					$mes = "Нет информации";
					file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");
				}
				else
				{
					$iid = $response->response[1]->user[0]->id;
					$iname = $response->response[1]->user[0]->first_name;
					$isname = $response->response[1]->user[0]->last_name;
					$iemail = $response->response[1]->show_email;
					if ($iemail == ""){$iemail ="0";}
					$iphone = $response->response[1]->show_old_phone;
					if ($iphone == ""){$iphone ="0";}
					$mes = "id: ".$iid."<br>name: ".$iname."<br>last_name: ".$isname."<br>email: ".$iemail."<br>phone: ".$iphone."<br>2fa: 0";
					if (isset($response->response[1]->show_email)!=true)
					{
						$mes = "id: ".$iid."<br>name: ".$iname."<br>last_name: ".$isname."<br>email: 1<br>phone: 1<br>2fa: 1";
					}
					file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");
				}
			}
			elseif($low_text == "p")
			{
				$mes = "peer: ".$peer_id."<br>from: ".$from_id;
				file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");
			}
			elseif ($low_text == 'warns')
			{
				if (isset($data->object->fwd_messages[0]))
				{
					$id = $data->object->fwd_messages[0]->from_id;
				}
				elseif(isset($data->object->reply_message->from_id))
				{
					$id = $data->object->reply_message->from_id;
				}
				else
				{
					$id = $from_id;
				}
				$warns = json_decode(file_get_contents($peer_id.'/warns.json'))->$id;
				if ($warns == "")
				{
					$warns = 0; 
				}
				$max_warns = db_get($peer_id.'/settings.json','MAX_WARNS');
				file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("У [id".$id."|вас] кол-во варнов равно ".$warns."/".$max_warns)."&access_token=".$token."&v=".$v."&random_id=0");
				
			}
			//	SHORT
			elseif(strpos($low_text,"short ") === 0)
			{
				$link = substr($text,6);
				$resp = json_decode(file_get_contents("https://api.vk.com/method/utils.checkLink?url=".urlencode($link)."&access_token=".$token."&v=".$v));
				if ($resp->response->status == "not_banned")
				{
					$shorted_link = json_decode(file_get_contents("https://api.vk.com/method/utils.getShortLink?url=".urlencode($link)."&access_token=".$token."&v=".$v));
					$mes = "your link:<br>".$shorted_link->response->short_url;
					if ($shorted_link->response->short_url == '')
					{
						$mes = 'no link';
					}
					file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");
				}
				else
				{
					file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("[id".$from_id."|Вашa] ссылка в бане")."&access_token=".$token."&v=".$v."&random_id=0");
				}
			}
			//	UNSHORT
			elseif(strpos($low_text,"unshort ") === 0)
			{
				$link = substr($text,8);
				$resp = json_decode(file_get_contents("https://api.vk.com/method/utils.checkLink?url=".urlencode($link)."&access_token=".$token."&v=".$v));
				$mes = "your link:<br>".$resp->response->link;
				file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");
			}
			//	REG
			elseif(strpos($low_text,"reg ") === 0)
			{
				$reg_id = substr($text,4);
				$resp = file_get_contents("https://vk.com/foaf.php?id=".$reg_id);
				$begin = strpos($resp,"<ya:created dc:date=\"");
				$resp = substr($resp,$begin+21);
				$end = strpos($resp,"+03:00\"/>");
				$unsorted_mes = substr($resp,0,$end);
				$unsorted_mes = explode('T', $unsorted_mes);
				$unsorted_date = explode("-" ,$unsorted_mes[0]);
				$time = $unsorted_mes[1];
				$mes = "date: ".$unsorted_date[2].".".$unsorted_date[1].".".$unsorted_date[0]."\ntime: ".$time;
				if ($time == '')
				{
					$mes = "no";
				}
				file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");
			}
			elseif (strpos($low_text, 'kick') === 0)
			{
				
				$admins = admins_list();
				if (in_array($from_id,$admins) or $from_id == $myid)
				{
					$id = substr($low_text, 4);
					if(strlen($id) == 0)
					{
						if (isset($data->object->fwd_messages[0]))
						{
							$id = $data->object->fwd_messages[0]->from_id;
						}
						elseif(isset($data->object->reply_message->from_id))
						{
							$id = $data->object->reply_message->from_id;
						}
					}
					elseif($id[0] == ' ')
					{
						if(strpos($id, 'join_by ') === 0)
						{
							#code...
						}
						elseif($id > 0)
						{
							$id = substr($id, 1);
						}
						else
						{
							echo 'ok';
							break;
						}
					}
					$id = check_id($id);
					if($id === false)
					{
						file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("access denied")."&access_token=".$token."&v=".$v."&random_id=0");

					}
					elseif ($id == $myid or $id == $group_id or $id == -$group_id or in_array($id,$admins) === true)
						{
							file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("access denied")."&access_token=".$token."&v=".$v."&random_id=0");
						}
					else
					{
						file_get_contents("https://api.vk.com/method/messages.removeChatUser?chat_id=".$chat_id."&member_id=".$id."&access_token=".$token."&v=".$v);
						db_set($peer_id.'/warns.json',$id,0);
					}

				}
				else
				{
					file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("access denied")."&access_token=".$token."&v=".$v."&random_id=0");
				}
			}

			elseif (strpos($low_text, 'perm') === 0)
			{
				$admins = admins_list();
				if (in_array($from_id,$admins) or $from_id == $myid)
				{
					$id = substr($low_text, 4);
					if(strlen($id) == 0)
					{
						if (isset($data->object->fwd_messages[0]))
						{
							$id = $data->object->fwd_messages[0]->from_id;
						}
						elseif(isset($data->object->reply_message->from_id))
						{
							$id = $data->object->reply_message->from_id;
						}
					}
					elseif($id[0] == ' ')
					{
						if(strpos($id, 'join_by ') === 0)
						{
							#code...
						}
						elseif($id > 0)
						{
							$id = substr($id, 1);
						}
						else
						{
							echo 'ok';
							break;
						}
					}
					$id = check_id($id);
					if($id == false)
					{
						file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("access denied")."&access_token=".$token."&v=".$v."&random_id=0");
						echo "ok";
						break;
					}
					if ($id == $myid or $id == $group_id or $id == -$group_id or in_array($id,$admins) === true)
					{
						file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("access denied")."&access_token=".$token."&v=".$v."&random_id=0");
					}
					else
					{
						$path = $peer_id.'/perm.json';
						$perms = json_decode(file_get_contents($path));
						if(!is_perm($peer_id,$id))
						{
							add_perm($peer_id,$id);
							file_get_contents("https://api.vk.com/method/messages.removeChatUser?chat_id=".$chat_id."&member_id=".$id."&access_token=".$token."&v=".$v);
							db_set($peer_id.'/warns.json',$id,0);
						}
					}
				}
				else
				{
					file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("access denied")."&access_token=".$token."&v=".$v."&random_id=0");
				}
			}

			elseif (strpos($low_text, 'unperm') === 0)
			{
				$admins = admins_list();
				if (in_array($from_id,$admins) or $from_id == $myid)
				{
					$id = substr($low_text, 6);
					if(strlen($id) == 0)
					{
						if (isset($data->object->fwd_messages[0]))
						{
							$id = $data->object->fwd_messages[0]->from_id;
						}
						elseif(isset($data->object->reply_message->from_id))
						{
							$id = $data->object->reply_message->from_id;
						}
					}
					elseif($id[0] == ' ')
					{
						if(strpos($id, 'join_by ') === 0)
						{
							#code...
						}
						elseif($id > 0)
						{
							$id = substr($id, 1);
						}
						else
						{
							echo 'ok';
							break;
						}
					}
					$id = check_id($id);
					if($id == false)
					{
						file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("access denied")."&access_token=".$token."&v=".$v."&random_id=0");
						echo "ok";
						break;
					}
					if ($id == $myid or $id == $group_id or $id == -$group_id or in_array($id,$admins) === true)
					{
						file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("access denied")."&access_token=".$token."&v=".$v."&random_id=0");
					}
					else
					{
						$path = $peer_id.'/perm.json';
						$perms = json_decode(file_get_contents($path));
						if(is_perm($peer_id,$id))
						{
							del_perm($peer_id,$id);
						}
					}
				}
				else
				{
					file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("access denied")."&access_token=".$token."&v=".$v."&random_id=0");
				}
			}

			elseif (strpos($low_text, 'warn') === 0)
			{
				$admins = admins_list();
				if (in_array($from_id,$admins) or $from_id == $myid)
				{
					$id = substr($low_text, 4);
					if(strlen($id) == 0)
					{
						if (isset($data->object->fwd_messages[0]))
						{
							$id = $data->object->fwd_messages[0]->from_id;
						}
						elseif(isset($data->object->reply_message->from_id))
						{
							$id = $data->object->reply_message->from_id;
						}
					}
					elseif($id[0] == ' ')
					{
						$id = substr($id, 1);

						if($id > 0)
						{
							$id = $id;
						}
						else
						{
							echo 'ok';
							break;
						}
					}
					$id = check_id($id);
					if($id == false)
					{
						file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("access denied")."&access_token=".$token."&v=".$v."&random_id=0");
						echo "ok";
						break;
					}
					if ($id == $myid or $id == $group_id or $id == -$group_id or in_array($id,$admins) === true)
					{
						file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("access denied")."&access_token=".$token."&v=".$v."&random_id=0");
					}
					else
					{
						$warns = json_decode(file_get_contents($peer_id.'/warns.json'))->$id;
						$max_warns = db_get($peer_id.'/settings.json','MAX_WARNS');
						$warns++;
						db_set($peer_id.'/warns.json',$id,$warns);
						file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("У [id".$id."|вас] кол-во варнов равно ".$warns."/".$max_warns)."&access_token=".$token."&v=".$v."&random_id=0");
						if($warns >= $max_warns)
						{
							file_get_contents("https://api.vk.com/method/messages.removeChatUser?chat_id=".$chat_id."&member_id=".$id."&access_token=".$token."&v=".$v);
							db_set($peer_id.'/warns.json',$id,0);
						}
					}
				}
				else
				{
					file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("access denied")."&access_token=".$token."&v=".$v."&random_id=0");
				}
			}

			elseif (strpos($low_text, 'unwarn') === 0)
			{
				$admins = admins_list();
				if (in_array($from_id,$admins) or $from_id == $myid)
				{
					$id = substr($low_text, 6);
					if(strlen($id) == 0)
					{
						if (isset($data->object->fwd_messages[0]))
						{
							$id = $data->object->fwd_messages[0]->from_id;
						}
						elseif(isset($data->object->reply_message->from_id))
						{
							$id = $data->object->reply_message->from_id;
						}
					}
					elseif($id[0] == ' ')
					{
						$id = substr($id, 1);

						if($id > 0)
						{
							$id = substr($id, 1);
						}
						else
						{
							echo 'ok';
							break;
						}
					}
					$id = check_id($id);
					if($id == false)
					{
						file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("access denied")."&access_token=".$token."&v=".$v."&random_id=0");
						echo "ok";
						break;
					}
					if ($id == $myid or $id == $group_id or $id == -$group_id or in_array($id,$admins) === true)
					{
						file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("access denied")."&access_token=".$token."&v=".$v."&random_id=0");
					}
					else
					{
						$warns = json_decode(file_get_contents($peer_id.'/warns.json'))->$id;
						$max_warns = db_get($peer_id.'/settings.json','MAX_WARNS');
						$warns--;
						if($warns < 0){
							$warns = 0;
						}
						if ($warns == "")
						{
							$warns = 0;
						}
						db_set($peer_id.'/warns.json',$id,$warns);
						file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("У [id".$id."|вас] кол-во варнов равно ".$warns."/".$max_warns)."&access_token=".$token."&v=".$v."&random_id=0");
					}
				}
				else
				{
					file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("access denied")."&access_token=".$token."&v=".$v."&random_id=0");
				}
			}

			elseif (strpos($low_text,'set ') === 0)
			{
				$admins = admins_list();
				if (in_array($from_id,$admins) or $from_id == $myid)
				{
					$params = explode(' ', $low_text,3);
					$big_params = explode(' ', $text,3);
					$param = $params[1];
					$settengs = db_get($peer_id.'/settings.json');
					if(isset($settengs[mb_strtoupper($param)]))
					{
						if(is_numeric($params[2]))
							{
								$value = (int) $params[2];
							}
						
							if($value < 0)
							{
								$value = 0;
							}
						if(strrpos('kick_', $param) === 0)
						{
							if($value > 1)
							{
								$value = 1;
							}
						}
						elseif($param == 'join_text')
						{
							$value = (string) $big_params[2];
						}
						elseif($param == 'max_warns')
						{
							if($value < 1)
							{
								$value = 1;
							}

							if($value > 15)
							{
								$value = 15;
							}
						}
						db_set($peer_id.'/settings.json',mb_strtoupper($param),$value);
						file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode('ok')."&access_token=".$token."&v=".$v."&random_id=0");
					}
					else
					{
						file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode('Такого параметра нет')."&access_token=".$token."&v=".$v."&random_id=0");
					}

				}	
				else
				{
					file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("access denied")."&access_token=".$token."&v=".$v."&random_id=0");
				}
			}

			elseif ($low_text == 'settings')
			{
				$admins = admins_list();
				if (in_array($from_id,$admins) or $from_id == $myid)
				{
					$settings = db_get($peer_id.'/settings.json');
					$mes = '';
					foreach ($settings as $key => $value)
					{
						$mes .= $key.' => '.$value."\n";
					}
					file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");

				}
				else
				{
					file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode("access denied")."&access_token=".$token."&v=".$v."&random_id=0");
				}
			}
			elseif (strpos($low_text,"eval ") === 0)
			{
				if ($from_id == $myid)
				{
					$command = substr($text,5);
					eval("$command");
				}
				else
				{
					$mes = "Пашол нахуй" ;
					file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($mes)."&access_token=".$token."&v=".$v."&random_id=0");
				}
			}
			elseif ($low_text == "contests")
			{
			    $get_contests = json_decode(file_get_contents("http://codeforces.com/api/contest.list"));
			    $contests = "";
			    for ($i=0;$i<count($get_contests->result);$i++)
			    {
			        if ($get_contests->result[$i]->phase == "BEFORE")
			        {
			            $name = $get_contests->result[$i]->name;
			            $started_after = -$get_contests->result[$i]->relativeTimeSeconds;
			            $started_after = date("H:i:s", mktime(0, 0, $started_after));
			            $mes = "Name: ".$name."\nWait: ".$started_after;
			            $contests = $contests.$mes."\n\n";
			        }
			        else{break;}
			    }
			    if ($contests === "")
			    {
			        $contests = "No contests";
			    }
			    file_get_contents("https://api.vk.com/method/messages.send?peer_id=".$peer_id."&message=".urlencode($contests)."&access_token=".$token."&v=".$v."&random_id=0");
			}

		}

		echo "ok";
		break;
}
