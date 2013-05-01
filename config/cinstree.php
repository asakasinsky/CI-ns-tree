<?php	if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CI-ns-tree Configuration
 */

// Set the table, which consists tree
$config['table'] = 'cinstree';

// Обязательные поля
$config['fields']['id'] = 'id';
$config['fields']['tree_id'] = 'tree_id';
$config['fields']['parent_id'] = 'parent_id';
$config['fields']['position'] = 'position';
$config['fields']['left'] = 'left';
$config['fields']['right'] = 'right';
$config['fields']['level'] = 'level';

/* End of file cinstree.php */ 
/* Location: ./sparks/CI-ns-tree/config/cinstree.php */