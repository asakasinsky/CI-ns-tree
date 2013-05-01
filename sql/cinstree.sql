CREATE TABLE `cinstree` (
  `id` int(255) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id of node',
  `tree_id` int(11) unsigned NOT NULL DEFAULT '1' COMMENT 'id of tree',
  `parent_id` int(255) unsigned DEFAULT NULL COMMENT 'id of parent node',
  `left` int(11) unsigned NOT NULL DEFAULT '1' COMMENT 'left pointer',
  `right` int(11) unsigned NOT NULL DEFAULT '2' COMMENT 'right pointer',
  `position` int(4) unsigned NOT NULL DEFAULT '999' COMMENT 'vertical position of node',
  `level` int(2) unsigned NOT NULL DEFAULT '0' COMMENT 'horisontal position of node',
  `type` varchar(40) NOT NULL DEFAULT 'ROOT' COMMENT 'type of node',
  `name` varchar(255) NOT NULL DEFAULT 'root',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
