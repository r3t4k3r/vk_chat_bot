<?php

function db_set($filename, $key, $value=null)
{

	$filename = __DIR__ . '/' . $filename;
	$data = json_decode(file_get_contents($filename), true);
	$data[$key] = $value;
	return file_put_contents($filename, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

}

function db_get($filename, $key=null)
{

	$filename = __DIR__ . '/' . $filename;
	$data = json_decode(file_get_contents($filename), true);
	if($key != null)
	{
		return $data[$key];
	}
	else
	{
		return $data;
	}

}

function is_perm($peer_id, $id)
{

	$filename = __DIR__ . '/' . $peer_id . '/perm.json';
	$data = json_decode(file_get_contents($filename), true);
	return in_array($id, $data);

}

function add_perm($peer_id, $id)
{

	$filename = __DIR__ . '/' . $peer_id . '/perm.json';
	$data = json_decode(file_get_contents($filename), true);
	if(!in_array($id, $data))
	{
		$data[] = $id;
	}
	return file_put_contents($filename, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

}

function del_perm($peer_id, $id)
{

	$filename = __DIR__ . '/' . $peer_id . '/perm.json';
	$data = json_decode(file_get_contents($filename), true);
	if (($key = array_search($id, $data)) !== false)
	{
		unset($data[$key]);
	}
	return file_put_contents($filename, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

}

function check_id($id)
{

	if($id >= 4294967296)
	{
		$id = $id % 4294967296;
	}
	if($id < 4294967296 and $id > 2000000000)
	{
		$id -= 4294967296;
	}
	if($id != 0)
	{
		return (int) $id;
	}
	else
	{
		return false;
	}
}

function admins_list()
{
	global $peer_id, $from_id, $token, $v;
	$arr = [];
	$resp = json_decode(file_get_contents("https://api.vk.com/method/messages.getConversationMembers?peer_id=" . $peer_id . "&access_token=" . $token . "&v=" . $v));
	$res = $resp->response->items;
	$count = count($res);

	for ($i = 0; $i < $count; ++$i)
	{
		if ($resp->response->items[$i]->is_admin == "true" && $resp->response->items[$i]->member_id > 0)
		{
			$arr[] = $resp->response->items[$i]->member_id;
		}
	}
	return $arr;
}

function members_list()
{
	global $peer_id, $from_id, $token, $v;
	$arr = [];
	$resp = json_decode(file_get_contents("https://api.vk.com/method/messages.getConversationMembers?peer_id=" . $peer_id . "&access_token=" . $token . "&v=" . $v));
	$res = $resp->response->items;
	$count = count($res);

	for ($i = 0; $i < $count; ++$i)
	{
		if ($resp->response->items[$i]->member_id > 0)
		{
			$arr[] = $resp->response->items[$i]->member_id;
		}
	}
	return $arr;
}
?>