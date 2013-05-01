<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Cinstree {

	// Structure table and fields
	protected $CI;
	var $table	= "cinstree";
	var    $tree_id	=	0;
	var $fields	= array
		(
			"id"		=> FALSE,
			"tree_id"	=> FALSE,
			"parent_id"	=> FALSE,
			"position"	=> FALSE,
			"left"		=> FALSE,
			"right"		=> FALSE,
			"level"		=> FALSE
		);
	var $add_fields = array();
	// Constructor
	function __construct($config = array())
	{
		$this->CI = & get_instance();
		if (count($config) > 0)
		{
			$this->initialize($config);
		}
		else
		{
			foreach($this->fields as $k => &$v) { $v = $k; }
		}
	}

	function initialize($config = array())
	{
		foreach ($config as $key => $val)
		{
			if (isset($this->$key))
			{
				$this->$key = $val;
			}
		}
		$this->fields = array_merge($this->fields, $this->add_fields);
	}

	function new_tree($tree_id=FALSE)
	{
		if( ! (int)$tree_id) return FALSE;
		$this->tree_id = $tree_id;
		$query = $this->CI->db->query(
			"SELECT * FROM `".$this->table.
			"` WHERE `".$this->fields["tree_id"]."` = ".(int) $tree_id);
		if ($query->num_rows() > 0) return FALSE;
		$data=array
			(
				$this->fields['tree_id']		=>	$this->tree_id,
				$this->fields['parent_id']		=>	'0',
				$this->fields['left']			=>	'1',
				$this->fields['right']			=>	'2',
				$this->fields['level']			=>	'0',
				$this->fields['type']			=>	'root'
			);
		if($this->CI->db->insert($this->table,$data))
		{
			return $this->CI->db->insert_id();
		}else
		{
			return FALSE;
		}
	}

	public function remove_tree($tree_id=FALSE)
	{
		if( ! (int)$tree_id) return FALSE;
		$data = array($this->fields['tree_id']	=>$tree_id);
		$result = ($this->CI->db->delete($this->table, $data)) ? TRUE : FALSE;
		$this->tree_id = 0;
		return $result;
	}
	public function select_tree($tree_id=FALSE)
	{
		if( ! (int)$tree_id) return FALSE;
		$this->tree_id = $tree_id;
		return TRUE;
	}

	public function set_data($node_id = FALSE, $data = array())
	{
		if( ! (int)$node_id OR count($data)==0) return FALSE;
		if(count($this->add_fields) == 0) return FALSE;
		$flag = FALSE;
		$this->CI->db->where('id', $node_id);
		foreach($this->add_fields as $k => $v)
		{
			if(isset($data[$k]))
			{
				$this->CI->db->set($this->fields[$v], $data[$k]);
				$flag = TRUE;
			}
		}
		if( ! $flag) return FALSE;
		$result = ($this->CI->db->update($this->table)) ? TRUE : FALSE;
		return $result;
	}

	public function get_root()
	{
		$this->CI->db->select('id');
		$query = $this->CI->db->get_where($this->table,array($this->fields["tree_id"]=>$this->tree_id, $this->fields["left"]=>1));
		if ($query->num_rows()>0)
		{
			return $query->row();
		}
		else
		{
			return FALSE;
		}
	}

	public function get_node_obj($id)
	{
		if( ! (int)$id) return FALSE;
		$query = $this->CI->db->query("" .
			"SELECT `".implode("` , `", $this->fields)."` ".
			"FROM `".$this->table."` ".
			"WHERE `".$this->fields["id"]."` = ".(int) $id." ".
			"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id);
		return $query->num_rows() === 0 ? FALSE : $query->row();
	}

	// public function get_node_arr($id)
	// {
	// 	if( ! (int)$id) return FALSE;
	// 	$query = $this->CI->db->query("" .
	// 		"SELECT `".implode("` , `", $this->fields)."` ".
	// 		"FROM `".$this->table."` ".
	// 		"WHERE `".$this->fields["id"]."` = ".(int) $id." ".
	// 		"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id);
	// 	return $query->num_rows() === 0 ? FALSE : $query->row_array();
	// }

	function get_node($id)
	{
		if( ! (int)$id) return FALSE;
		$query = $this->CI->db->query("" .
			"SELECT `".implode("` , `", $this->fields)."` ".
			"FROM `".$this->table."` ".
			"WHERE `".$this->fields["id"]."` = ".(int) $id." ".
			"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id);
		return $query->num_rows() === 0 ? FALSE : $query->row_array();
	}

	function get_children_obj($id, $recursive = FALSE, $with_parent = FALSE)
	{
		if( ! (int)$id) return FALSE;
		$left = ( !$with_parent ) ? '> ' : '>= ';
		$right = ( !$with_parent ) ? '< ' : '<= ';
		$children = array();
		if($recursive)
		{
			$node = $this->get_node($id);
			$query = $this->CI->db->query("" .
				"SELECT `".implode("` , `", $this->fields)."` ".
				"FROM `".$this->table."` ".
				"WHERE `".$this->fields["left"]."` ". $left. (int) $node[$this->fields["left"]]." ".
				"AND `".$this->fields["right"]."` ". $right. (int) $node[$this->fields["right"]]." ".
				"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id." ".
				"ORDER BY `".$this->fields["left"]."` ASC");
		}
		else
		{
			$query = $this->CI->db->query("" .
				"SELECT `".implode("` , `", $this->fields)."` FROM `".$this->table."` ".
				"WHERE `".$this->fields["parent_id"]."` = ".(int) $id." ".
				"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id." ".
				"ORDER BY `".$this->fields["position"]."` ASC");
		}
		return $query->result();
	}

	function get_children_flat($id, $output = "tree", $recursive = FALSE)
	{
		if( ! (int)$id) return FALSE;
		$children = array();
		if($output == "flat")
		{
			$order_field = $this->fields["parent_id"];
		}
		else
		{
			$order_field = $this->fields["left"];
		}
		if($recursive)
		{
			$node = $this->get_node($id);
			$query = $this->CI->db->query("" .
				"SELECT `".implode("` , `", $this->fields)."` ".
				"FROM `".$this->table."` ".
				"WHERE `".$this->fields["left"]."` > ".(int) $node[$this->fields["left"]]." ".
				"AND `".$this->fields["right"]."` < ".(int) $node[$this->fields["right"]]." ".
				"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id." ".
				"ORDER BY `".$order_field."` ASC, `".$this->fields["position"]."` ASC");
		}
		else
		{
			$query = $this->CI->db->query("" .
				"SELECT `".implode("` , `", $this->fields)."` FROM `".$this->table."` ".
				"WHERE `".$this->fields["parent_id"]."` = ".(int) $id." ".
				"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id." ".
				"ORDER BY `".$this->fields["position"]."` ASC");
		}
		foreach($query->result_array() as $row) $children[$this->f($row[$this->fields["id"]])] = $row;
		return $children;
	}

	function get_children_flat_2($id, $output = "tree", $recursive = FALSE)
	{
		if( ! (int)$id) return FALSE;
		$children = array();
		if($output == "flat")
		{
			$order_field = $this->fields["parent_id"];
		}
		else
		{
			$order_field = $this->fields["left"];
		}
		if($recursive)
		{
			$node = $this->get_node($id);
			$query = $this->CI->db->query("" .
				"SELECT `".implode("` , `", $this->fields)."` ".
				"FROM `".$this->table."` ".
				"WHERE `".$this->fields["left"]."` >= ".(int) $node[$this->fields["left"]]." ".
				"AND `".$this->fields["right"]."` <= ".(int) $node[$this->fields["right"]]." ".
				"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id." ".
				"ORDER BY `".$order_field."` ASC, `".$this->fields["position"]."` ASC");
		}
		else
		{
			$query = $this->CI->db->query("" .
				"SELECT `".implode("` , `", $this->fields)."` FROM `".$this->table."` ".
				"WHERE `".$this->fields["parent_id"]."` = ".(int) $id." ".
				"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id." ".
				"ORDER BY `".$this->fields["position"]."` ASC");
		}
		foreach($query->result_array() as $row) $children[] = $row;
		return $children;
	}

	function get_children($id, $recursive = false)
	{
		if(!(int)$id) return FALSE;
		$children = array();
		if($recursive)
		{
			$node = $this->get_node($id);
			$query = $this->CI->db->query("" .
				"SELECT `".implode("` , `", $this->fields)."` ".
				"FROM `".$this->table."` ".
				"WHERE `".$this->fields["left"]."` >= ".(int) $node[$this->fields["left"]]." ".
				"AND `".$this->fields["right"]."` <= ".(int) $node[$this->fields["right"]]." ".
				"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id." ".
				"ORDER BY `".$this->fields["left"]."` ASC");
		}
		else
		{
			$query = $this->CI->db->query("" .
				"SELECT `".implode("` , `", $this->fields)."` FROM `".$this->table."` ".
				"WHERE `".$this->fields["parent_id"]."` = ".(int) $id." ".
				"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id." ".
				"ORDER BY `".$this->fields["position"]."` ASC");
		}
		foreach($query->result_array() as $row) $children[$this->f($row[$this->fields["id"]])] = $row;
		return $children;
	}



	function create($parent, $position)
	{
		return $this->move(0, $parent, $position);
	}

	function remove($id)
	{
		if((int)$id === 1) { return FALSE; }
		$data = $this->get_node($id);
		if($data===FALSE) return FALSE;
		$lft = (int)$data[$this->fields["left"]];
		$rgt = (int)$data[$this->fields["right"]];
		$dif = $rgt - $lft + 1;

		// deleting node and its children
		$this->CI->db->query("" .
			"DELETE FROM `".$this->table."` " .
			"WHERE `".$this->fields["left"]."` >= ".$lft." ".
			"AND `".$this->fields["right"]."` <= ".$rgt." ".
			"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id
		);
		// shift left indexes of nodes right of the node
		$this->CI->db->query("".
			"UPDATE `".$this->table."` " .
			"SET `".$this->fields["left"]."` = `".$this->fields["left"]."` - ".$dif." " .
			"WHERE `".$this->fields["left"]."` > ".$rgt." ".
			"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id
		);
		// shift right indexes of nodes right of the node and the node's parents
		$this->CI->db->query("" .
			"UPDATE `".$this->table."` " .
			"SET `".$this->fields["right"]."` = `".$this->fields["right"]."` - ".$dif." " .
			"WHERE `".$this->fields["right"]."` > ".$lft." ".
			"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id
		);

		$pid = (int)$data[$this->fields["parent_id"]];
		$pos = (int)$data[$this->fields["position"]];

		// Update position of siblings below the deleted node
		$this->CI->db->query("" .
			"UPDATE `".$this->table."` " .
			"SET `".$this->fields["position"]."` = `".$this->fields["position"]."` - 1 " .
			"WHERE `".$this->fields["parent_id"]."` = ".$pid." ".
			"AND `".$this->fields["position"]."` > ".$pos." ".
			"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id
		);

		return true;
	}

	function move($id, $ref_id, $position = 0, $is_copy = FALSE)
	{
		if((int)$ref_id === 0 || (int)$id === 1) { return FALSE; }
		$sql		= array();						// Queries executed at the end
		$node		= $this->get_node($id);		// Node data
		$nchildren	= $this->get_children($id);	// Node children
		$ref_node	= $this->get_node($ref_id);	// Ref node data
		$rchildren	= $this->get_children($ref_id);// Ref node children

		$ndif = 2;
		$node_ids = array(-1);
		if($node !== FALSE)
		{
			$node_ids = array_keys($this->get_children($id, true));
			// TODO: should be !$is_copy && , but if copied to self - screws some right indexes
			if(in_array($ref_id, $node_ids)) return FALSE;
			$ndif = $node[$this->fields["right"]] - $node[$this->fields["left"]] + 1;
		}
		if($position >= count($rchildren))
		{
			$position = count($rchildren);
		}

		// Not creating or copying - old parent is cleaned
		if($node !== FALSE && $is_copy == FALSE)
		{
			$sql[] = "" .
				"UPDATE `".$this->table."` " .
					"SET `".$this->fields["position"]."` = `".$this->fields["position"]."` - 1 " .
				"WHERE " .
					"`".$this->fields["parent_id"]."` = ".$node[$this->fields["parent_id"]]." AND " .
					"`".$this->fields["position"]."` > ".$node[$this->fields["position"]]." ".
			"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id;
			$sql[] = "" .
				"UPDATE `".$this->table."` " .
					"SET `".$this->fields["left"]."` = `".$this->fields["left"]."` - ".$ndif." " .
				"WHERE `".$this->fields["left"]."` > ".$node[$this->fields["right"]]." ".
			"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id;
			$sql[] = "" .
				"UPDATE `".$this->table."` " .
					"SET `".$this->fields["right"]."` = `".$this->fields["right"]."` - ".$ndif." " .
				"WHERE " .
					"`".$this->fields["right"]."` > ".$node[$this->fields["left"]]." AND " .
					"`".$this->fields["id"]."` NOT IN (".implode(",", $node_ids).") "." ".
			"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id.";";
		}
		// Preparing new parent
		$sql[] = "" .
			"UPDATE `".$this->table."` " .
				"SET `".$this->fields["position"]."` = `".$this->fields["position"]."` + 1 " .
			"WHERE " .
				"`".$this->fields["parent_id"]."` = ".$ref_id." AND " .
				"`".$this->fields["position"]."` >= ".$position." " .
				( $is_copy ? "" : " AND `".$this->fields["id"]."` NOT IN (".implode(",", $node_ids).") ")." ".
			"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id;

		$ref_ind = $ref_id === 0 ? (int)$rchildren[count($rchildren) - 1][$this->fields["right"]] + 1 : (int)$ref_node[$this->fields["right"]];
		$ref_ind = max($ref_ind, 1);

		$self = ($node !== FALSE && !$is_copy && (int)$node[$this->fields["parent_id"]] == $ref_id && $position > $node[$this->fields["position"]]) ? 1 : 0;
		foreach($rchildren as $k => $v)
		{
			if($v[$this->fields["position"]] - $self == $position)
			{
				$ref_ind = (int)$v[$this->fields["left"]];
				break;
			}
		}
		if($node !== FALSE && !$is_copy && $node[$this->fields["left"]] < $ref_ind)
		{
			$ref_ind -= $ndif;
		}

		$sql[] = "" .
			"UPDATE `".$this->table."` " .
				"SET `".$this->fields["left"]."` = `".$this->fields["left"]."` + ".$ndif." " .
			"WHERE " .
				"`".$this->fields["left"]."` >= ".$ref_ind." " .
				( $is_copy ? "" : " AND `".$this->fields["id"]."` NOT IN (".implode(",", $node_ids).") ")." ".
			"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id;
		$sql[] = "" .
			"UPDATE `".$this->table."` " .
				"SET `".$this->fields["right"]."` = `".$this->fields["right"]."` + ".$ndif." " .
			"WHERE " .
				"`".$this->fields["right"]."` >= ".$ref_ind." " .
				( $is_copy ? "" : " AND `".$this->fields["id"]."` NOT IN (".implode(",", $node_ids).") ")." ".
			"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id;

		$ldif = $ref_id == 0 ? 0 : $ref_node[$this->fields["level"]] + 1;
		$idif = $ref_ind;
		if($node !== FALSE)
		{
			$ldif = $node[$this->fields["level"]] - ($ref_node[$this->fields["level"]] + 1);
			$idif = $node[$this->fields["left"]] - $ref_ind;
			if($is_copy)
			{
				$temp_string = '';
				foreach ($this->add_fields as $k => $v) {
					$temp_string.= "`".$k."`, ";
				}
				$sql[] = "" .
					"INSERT INTO `".$this->table."` (" .
						"`".$this->fields["parent_id"]."`, " .
						"`".$this->fields["position"]."`, " .
						"`".$this->fields["left"]."`, " .
						"`".$this->fields["right"]."`, " .
						"`".$this->fields["level"]."`, " .
						$temp_string.
						"`".$this->fields["tree_id"]."`" .
					") " .
						"SELECT " .
							"".$ref_id.", " .
							"`".$this->fields["position"]."`, " .
							"`".$this->fields["left"]."` - (".($idif + ($node[$this->fields["left"]] >= $ref_ind ? $ndif : 0))."), " .
							"`".$this->fields["right"]."` - (".($idif + ($node[$this->fields["left"]] >= $ref_ind ? $ndif : 0))."), " .
							"`".$this->fields["level"]."` - (".$ldif."), " .
							$temp_string.
							"`".$this->fields["tree_id"]."` " .
						"FROM `".$this->table."` " .
						"WHERE " .
							"`".$this->fields["id"]."` IN (".implode(",", $node_ids).") " .
						"ORDER BY `".$this->fields["level"]."` ASC";
			}
			else
			{
				$sql[] = "" .
					"UPDATE `".$this->table."` SET " .
						"`".$this->fields["parent_id"]."` = ".$ref_id.", " .
						"`".$this->fields["position"]."` = ".$position." " .
					"WHERE " .
						"`".$this->fields["id"]."` = ".$id." ".
					"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id;
				$sql[] = "" .
					"UPDATE `".$this->table."` SET " .
						"`".$this->fields["left"]."` = `".$this->fields["left"]."` - (".$idif."), " .
						"`".$this->fields["right"]."` = `".$this->fields["right"]."` - (".$idif."), " .
						"`".$this->fields["level"]."` = `".$this->fields["level"]."` - (".$ldif.") " .
					"WHERE " .
						"`".$this->fields["id"]."` IN (".implode(",", $node_ids).") "." ".
					"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id;
			}
		}
		else
		{
			$sql[] = "" .
				"INSERT INTO `".$this->table."` (" .
					"`".$this->fields["parent_id"]."`, " .
					"`".$this->fields["position"]."`, " .
					"`".$this->fields["tree_id"]."`, " .
					"`".$this->fields["left"]."`, " .
					"`".$this->fields["right"]."`, " .
					"`".$this->fields["level"]."` " .
					") " .
				"VALUES (" .
					$ref_id.", " .
					$position.", " .
					$this->tree_id.", " .
					$idif.", " .
					($idif + 1).", " .
					$ldif.
				")";
		}
		foreach($sql as $q) { $this->CI->db->query($q); }
		$ind = $this->CI->db->insert_id();
		if($is_copy) $this->fix_copy($ind, $position);
		return $node === FALSE || $is_copy ? $ind : true;
	}

	function fix_copy($id, $position)
	{
		$node = $this->get_node($id);
		$children = $this->get_children($id, true);

		$map = array();
		for($i = $node[$this->fields["left"]] + 1; $i < $node[$this->fields["right"]]; $i++)
		{
			$map[$i] = $id;
		}
		foreach($children as $cid => $child)
		{
			if((int)$cid == (int)$id)
			{
				$this->CI->db->query("UPDATE `".$this->table."` SET `".$this->fields["position"]."` = ".$position." WHERE `".$this->fields["id"]."` = ".$cid);
				continue;
			}
			$this->CI->db->query("UPDATE `".$this->table."` SET `".$this->fields["parent_id"]."` = ".$map[(int)$child[$this->fields["left"]]]." WHERE `".$this->fields["id"]."` = ".$cid);
			for($i = $child[$this->fields["left"]] + 1; $i < $child[$this->fields["right"]]; $i++)
			{
				$map[$i] = $cid;
			}
		}
	}

	function analyze()
	{
		$report = array();

		$query = $this->CI->db->query("" .
			"SELECT " .
				"`".$this->fields["left"]."` FROM `".$this->table."` s " .
			"WHERE " .
				"`".$this->fields["parent_id"]."` = 0 "." ".
			"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id
		);
		if($query->num_rows() == 0)
		{
			$report[] = "[FAIL]\tNo root node.";
		}
		else
		{
			$report[] = ($query->num_rows() > 1) ? "[FAIL]\tMore than one root node." : "[OK]\tJust one root node.";
		}
		$row = $this->fetch_assoc($query);
		$report[] = ($this->f($row[0]) != 1) ? "[FAIL]\tRoot node's left index is not 1." : "[OK]\tRoot node's left index is 1.";

		$query = $this->CI->db->query("" .
			"SELECT " .
				"COUNT(*) FROM `".$this->table."` s " .
			"WHERE " .
				"`".$this->fields["parent_id"]."` != 0 AND " .
				"(SELECT COUNT(*) FROM `".$this->table."` WHERE `".$this->fields["id"]."` = s.`".$this->fields["parent_id"]."`) = 0 "." ".
			"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id);
		$row = $this->fetch_assoc($query);
		$report[] = ($this->f($row[0]) > 0) ? "[FAIL]\tMissing parents." : "[OK]\tNo missing parents.";

		$query = $this->CI->db->query("SELECT MAX(`".$this->fields["right"]."`) FROM `".$this->table."`"." ".
			"WHERE `".$this->fields["tree_id"]."` = ".(int) $this->tree_id);
		$row = $this->fetch_assoc($query);
		$n = $this->f($row[0]);
		$query = $this->CI->db->query("SELECT COUNT(*) FROM `".$this->table."`"." ".
			"WHERE `".$this->fields["tree_id"]."` = ".(int) $this->tree_id);
		$row = $this->fetch_assoc($query);
		$c = $this->f($row[0]);
		$report[] = ($n/2 != $c) ? "[FAIL]\tRight index does not match node count." : "[OK]\tRight index matches count.";

		$query = $this->CI->db->query("" .
			"SELECT COUNT(`".$this->fields["id"]."`) FROM `".$this->table."` s " .
			"WHERE " .
				"(SELECT COUNT(*) FROM `".$this->table."` WHERE " .
					"`".$this->fields["right"]."` < s.`".$this->fields["right"]."` AND " .
					"`".$this->fields["left"]."` > s.`".$this->fields["left"]."` AND " .
					"`".$this->fields["level"]."` = s.`".$this->fields["level"]."` + 1" .
				") != " .
				"(SELECT COUNT(*) FROM `".$this->table."` WHERE " .
					"`".$this->fields["parent_id"]."` = s.`".$this->fields["id"]."`" .
				") "." ".
			"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id
			);
		$row = $this->fetch_assoc($query);
		$report[] = ($this->f($row[0]) > 0) ? "[FAIL]\tAdjacency and nested set do not match." : "[OK]\tNS and AJ match";

		return implode("<br />",$report);
	}

	function dump($output = FALSE)
	{
		$nodes = array();
		$query = $this->CI->db->query("SELECT * FROM ".$this->table." ".
			"WHERE `".$this->fields["tree_id"]."` = ".(int) $this->tree_id." ".
			"AND `".$this->fields["tree_id"]."` = ".(int) $this->tree_id." ".
			"ORDER BY `".$this->fields["left"]."`");
		foreach($query->result_array() as $row) $nodes[] = $row;
		if($output)
		{
			echo "<pre>";
			foreach($nodes as $node)
			{
				echo str_repeat("&#160;",(int)$node[$this->fields["level"]] * 2);
				echo $node[$this->fields["id"]]." (".$node[$this->fields["left"]].",".$node[$this->fields["right"]].",".$node[$this->fields["level"]].",".$node[$this->fields["parent_id"]].",".$node[$this->fields["position"]].")<br />";
			}
			echo str_repeat("-",40);
			echo "</pre>";
		}
		return $nodes;
	}
	function drop_table()
	{
		$this->CI->db->query("TRUNCATE TABLE `".$this->table."`");
		$this->CI->db->query("" .
				"INSERT INTO `".$this->table."` (" .
					"`".$this->fields["id"]."`, " .
					"`".$this->fields["parent_id"]."`, " .
					"`".$this->fields["position"]."`, " .
					"`".$this->fields["left"]."`, " .
					"`".$this->fields["right"]."`, " .
					"`".$this->fields["level"]."` " .
					") " .
				"VALUES (" .
					"1, " .
					"0, " .
					"0, " .
					"1, " .
					"2, " .
					"0 ".
				")");
	}



	private function f($what)
	{
		return stripslashes($what);
	}

	private function fetch_assoc($query)
	{
		$result = $this->CI->db->call_function('fetch_array',$query->result_id, MYSQL_BOTH);
		return (!$result)? FALSE : $result;
	}
}


/* End of file cinstree.php */
/* Location: ./sparks/CI-ns-tree/libraries/cinstree.php */
