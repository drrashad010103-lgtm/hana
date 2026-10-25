<?php
return array (
  'generated' => '2026-10-21T15:59:34+03:00',
  'tables' => 
  array (
    '_orphan_sub' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `_orphan_sub` (
  `id` int(11) DEFAULT NULL,
  `proc_id` int(11) DEFAULT NULL,
  `proc_type` int(11) DEFAULT NULL,
  `currency_from` int(11) DEFAULT NULL,
  `unit` int(11) DEFAULT NULL,
  `i1` bigint(20) DEFAULT NULL,
  `i2` bigint(20) DEFAULT NULL,
  `val` double DEFAULT NULL,
  `val1` double DEFAULT NULL,
  `exchange_price` double DEFAULT NULL,
  `expire_date` datetime DEFAULT NULL,
  `taxperc` double DEFAULT NULL,
  `taxval` double DEFAULT NULL,
  `discount` double DEFAULT NULL,
  `expense` double DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 DEFAULT NULL,
  `Txtr` text CHARACTER SET utf8mb4 DEFAULT NULL,
  `kiychen_printed` tinyint(1) DEFAULT NULL,
  `invpurch` text CHARACTER SET utf8mb4 DEFAULT NULL,
  `barcode` text CHARACTER SET utf8mb4 DEFAULT NULL,
  `sale_price` int(11) DEFAULT NULL,
  `kiychen_ok` tinyint(1) DEFAULT NULL,
  `kiychen_no` tinyint(1) DEFAULT NULL,
  `kiychen_done` tinyint(1) DEFAULT NULL,
  `kiychen_goes` tinyint(1) DEFAULT NULL,
  `kiychen_note` text CHARACTER SET utf8mb4 DEFAULT NULL,
  `date_goes` datetime DEFAULT NULL,
  `date_date_Done` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'proc_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'proc_type' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'currency_from' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'unit' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'i1' => 
        array (
          'type' => 'bigint(20)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'i2' => 
        array (
          'type' => 'bigint(20)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'val' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'val1' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'exchange_price' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'expire_date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'taxperc' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'taxval' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'discount' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'expense' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Txtr' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'kiychen_printed' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'invpurch' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'barcode' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'sale_price' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'kiychen_ok' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'kiychen_no' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'kiychen_done' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'kiychen_goes' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'kiychen_note' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date_goes' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date_date_Done' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'accounts_index' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `accounts_index` (
  `Code` bigint(20) NOT NULL,
  `AName` text DEFAULT NULL,
  `Nature` int(11) DEFAULT NULL,
  `Type` int(11) DEFAULT NULL,
  `ParentCode` bigint(20) DEFAULT NULL,
  `Date` datetime DEFAULT NULL,
  `UserName` text DEFAULT NULL,
  `IValue` double DEFAULT NULL,
  `FinalAcc` int(11) DEFAULT NULL,
  `Total_Debts` double DEFAULT NULL,
  `Total_Credits` double DEFAULT NULL,
  `Account_Value` double DEFAULT NULL,
  `Acc_branch` int(11) DEFAULT NULL,
  `acc_level` int(11) DEFAULT NULL,
  `is_center` tinyint(1) DEFAULT NULL,
  `default_center` int(11) DEFAULT NULL,
  `cust_type` int(11) DEFAULT NULL,
  PRIMARY KEY (`Code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'Code' => 
        array (
          'type' => 'bigint(20)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'AName' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Nature' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Type' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'ParentCode' => 
        array (
          'type' => 'bigint(20)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'UserName' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IValue' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'FinalAcc' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Total_Debts' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Total_Credits' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Account_Value' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Acc_branch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'acc_level' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'is_center' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'default_center' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'cust_type' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'app_settings' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `app_settings` (
  `k` varchar(64) NOT NULL,
  `v` text DEFAULT NULL,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'k' => 
        array (
          'type' => 'varchar(64)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'v' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'app_users' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `app_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL,
  `pass_hash` varchar(255) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => 'auto_increment',
          'gen' => NULL,
        ),
        'username' => 
        array (
          'type' => 'varchar(64)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'pass_hash' => 
        array (
          'type' => 'varchar(255)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'active' => 
        array (
          'type' => 'tinyint(4)',
          'null' => 'NO',
          'default' => '1',
          'extra' => '',
          'gen' => NULL,
        ),
        'created_at' => 
        array (
          'type' => 'timestamp',
          'null' => 'YES',
          'default' => 'current_timestamp()',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'areas' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `areas` (
  `id` int(11) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `city` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'city' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'audit_log' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ts` datetime NOT NULL,
  `uid` int(11) NOT NULL DEFAULT 0,
  `uname` varchar(64) NOT NULL DEFAULT \'\',
  `action` varchar(16) NOT NULL,
  `entity` varchar(32) NOT NULL,
  `entity_id` bigint(20) NOT NULL DEFAULT 0,
  `detail` varchar(500) NOT NULL DEFAULT \'\',
  `ip` varchar(45) NOT NULL DEFAULT \'\',
  PRIMARY KEY (`id`),
  KEY `audit_ts` (`ts`),
  KEY `audit_ent` (`entity`,`entity_id`),
  KEY `audit_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'bigint(20)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => 'auto_increment',
          'gen' => NULL,
        ),
        'ts' => 
        array (
          'type' => 'datetime',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'uid' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => '0',
          'extra' => '',
          'gen' => NULL,
        ),
        'uname' => 
        array (
          'type' => 'varchar(64)',
          'null' => 'NO',
          'default' => '\'\'',
          'extra' => '',
          'gen' => NULL,
        ),
        'action' => 
        array (
          'type' => 'varchar(16)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'entity' => 
        array (
          'type' => 'varchar(32)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'entity_id' => 
        array (
          'type' => 'bigint(20)',
          'null' => 'NO',
          'default' => '0',
          'extra' => '',
          'gen' => NULL,
        ),
        'detail' => 
        array (
          'type' => 'varchar(500)',
          'null' => 'NO',
          'default' => '\'\'',
          'extra' => '',
          'gen' => NULL,
        ),
        'ip' => 
        array (
          'type' => 'varchar(45)',
          'null' => 'NO',
          'default' => '\'\'',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'banks' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `banks` (
  `id` int(11) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `country` int(11) DEFAULT NULL,
  `city` int(11) DEFAULT NULL,
  `area` int(11) DEFAULT NULL,
  `tel` text DEFAULT NULL,
  `mobile` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `IS_Deleted` tinyint(1) DEFAULT NULL,
  `accountNo` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'country' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'city' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'area' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tel' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'mobile' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_Deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'accountNo' => 
        array (
          'type' => 'bigint(20)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'branches' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `branches` (
  `id` int(11) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `Tel` text DEFAULT NULL,
  `Mobile` text DEFAULT NULL,
  `Fax` text DEFAULT NULL,
  `Email` text DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `IS_Deleted` tinyint(1) DEFAULT NULL,
  `is_main` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Tel' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Mobile' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Fax' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Email' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Address' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_Deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'is_main' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'cities' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `cities` (
  `id` int(11) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `country` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'country' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'coboun' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `coboun` (
  `id` int(11) DEFAULT NULL,
  `code_name` text DEFAULT NULL,
  `date_start` date DEFAULT NULL,
  `date_end` date DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `discount` int(11) DEFAULT NULL,
  `for_clients` int(11) DEFAULT NULL,
  `Is_deleted` tinyint(1) DEFAULT NULL,
  `clients_group` int(11) DEFAULT NULL,
  `Branch` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'code_name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date_start' => 
        array (
          'type' => 'date',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date_end' => 
        array (
          'type' => 'date',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'active' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'discount' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'for_clients' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Is_deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'clients_group' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Branch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'companies' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `companies` (
  `id` int(11) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `Tel` text DEFAULT NULL,
  `Mobile` text DEFAULT NULL,
  `Fax` text DEFAULT NULL,
  `Email` text DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `dbname` text DEFAULT NULL,
  `IS_Deleted` tinyint(1) DEFAULT NULL,
  `name_en` varchar(128) DEFAULT \'\',
  `activity` varchar(128) DEFAULT \'\',
  `license_no` varchar(64) DEFAULT \'\',
  `comm_reg` varchar(64) DEFAULT \'\',
  `tax_no` varchar(64) DEFAULT \'\',
  `tax_pct` double DEFAULT 0,
  `email2` varchar(128) DEFAULT \'\',
  `website` varchar(128) DEFAULT \'\',
  `address2` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Tel' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Mobile' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Fax' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Email' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Address' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'dbname' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_Deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'name_en' => 
        array (
          'type' => 'varchar(128)',
          'null' => 'YES',
          'default' => '\'\'',
          'extra' => '',
          'gen' => NULL,
        ),
        'activity' => 
        array (
          'type' => 'varchar(128)',
          'null' => 'YES',
          'default' => '\'\'',
          'extra' => '',
          'gen' => NULL,
        ),
        'license_no' => 
        array (
          'type' => 'varchar(64)',
          'null' => 'YES',
          'default' => '\'\'',
          'extra' => '',
          'gen' => NULL,
        ),
        'comm_reg' => 
        array (
          'type' => 'varchar(64)',
          'null' => 'YES',
          'default' => '\'\'',
          'extra' => '',
          'gen' => NULL,
        ),
        'tax_no' => 
        array (
          'type' => 'varchar(64)',
          'null' => 'YES',
          'default' => '\'\'',
          'extra' => '',
          'gen' => NULL,
        ),
        'tax_pct' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => '0',
          'extra' => '',
          'gen' => NULL,
        ),
        'email2' => 
        array (
          'type' => 'varchar(128)',
          'null' => 'YES',
          'default' => '\'\'',
          'extra' => '',
          'gen' => NULL,
        ),
        'website' => 
        array (
          'type' => 'varchar(128)',
          'null' => 'YES',
          'default' => '\'\'',
          'extra' => '',
          'gen' => NULL,
        ),
        'address2' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'count_list' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `count_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` int(11) NOT NULL,
  `name` text NOT NULL,
  `counted` double NOT NULL DEFAULT 0,
  `balance` double NOT NULL DEFAULT 0,
  `exp_date` varchar(12) NOT NULL DEFAULT \'\',
  `note` text DEFAULT NULL,
  `username` varchar(64) NOT NULL DEFAULT \'\',
  `posted` tinyint(4) NOT NULL DEFAULT 0,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => 'auto_increment',
          'gen' => NULL,
        ),
        'code' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'counted' => 
        array (
          'type' => 'double',
          'null' => 'NO',
          'default' => '0',
          'extra' => '',
          'gen' => NULL,
        ),
        'balance' => 
        array (
          'type' => 'double',
          'null' => 'NO',
          'default' => '0',
          'extra' => '',
          'gen' => NULL,
        ),
        'exp_date' => 
        array (
          'type' => 'varchar(12)',
          'null' => 'NO',
          'default' => '\'\'',
          'extra' => '',
          'gen' => NULL,
        ),
        'note' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'username' => 
        array (
          'type' => 'varchar(64)',
          'null' => 'NO',
          'default' => '\'\'',
          'extra' => '',
          'gen' => NULL,
        ),
        'posted' => 
        array (
          'type' => 'tinyint(4)',
          'null' => 'NO',
          'default' => '0',
          'extra' => '',
          'gen' => NULL,
        ),
        'created' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'current_timestamp()',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'countries' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `countries` (
  `id` int(11) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `nationality` text DEFAULT NULL,
  `code` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'nationality' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'code' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'curr_barcodes' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `curr_barcodes` (
  `curr` int(11) DEFAULT NULL,
  `barcode` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'curr' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'barcode' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'curr_sup' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `curr_sup` (
  `curr` int(11) DEFAULT NULL,
  `sup_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'curr' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'sup_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'curr_units' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `curr_units` (
  `curr` int(11) DEFAULT NULL,
  `unit` int(11) DEFAULT NULL,
  `perc` double DEFAULT NULL,
  `purch` double DEFAULT NULL,
  `sale` double DEFAULT NULL,
  `barcode` text DEFAULT NULL,
  `offer` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'curr' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'unit' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'perc' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'purch' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'sale' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'barcode' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'offer' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'currencies' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `currencies` (
  `id` int(11) DEFAULT NULL,
  `symbol` text DEFAULT NULL,
  `nameEN` text DEFAULT NULL,
  `barcode` text DEFAULT NULL,
  `is_mezan` tinyint(1) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `unit` int(11) DEFAULT NULL,
  `IS_Deleted` tinyint(1) DEFAULT NULL,
  `purch_price` double DEFAULT NULL,
  `purchdollar` double DEFAULT NULL,
  `sale_price` double DEFAULT NULL,
  `saledollar` double DEFAULT NULL,
  `sale_reb7` double DEFAULT NULL,
  `limit` int(11) DEFAULT NULL,
  `tax` double DEFAULT NULL,
  `discount` double DEFAULT NULL,
  `tax_group` int(11) DEFAULT NULL,
  `minprice` double DEFAULT NULL,
  `group2` int(11) DEFAULT NULL,
  `wholesale` int(11) DEFAULT NULL,
  `offersale` int(11) DEFAULT NULL,
  `whole_price` double DEFAULT NULL,
  `G_reb7` double DEFAULT NULL,
  `offer_price` double DEFAULT NULL,
  `D_reb7` double DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `no` text DEFAULT NULL,
  `ref_no` text DEFAULT NULL,
  `ref_vendor` int(11) DEFAULT NULL,
  `ref_price` double DEFAULT NULL,
  `ref_quan` double DEFAULT NULL,
  `outoffer` tinyint(1) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `stock_address` text DEFAULT NULL,
  `i1` int(11) DEFAULT NULL,
  `i2` int(11) DEFAULT NULL,
  `imge` text DEFAULT NULL,
  `web_sku` text DEFAULT NULL,
  `web_item_category_id` text DEFAULT NULL,
  `chk_fast_menu` tinyint(1) DEFAULT NULL,
  `item_orders` int(11) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `slug` text DEFAULT NULL,
  `offer_until` datetime DEFAULT NULL,
  UNIQUE KEY `ux_cur_id` (`id`),
  KEY `ix_cur_grp` (`group_id`),
  KEY `ix_cur_unit` (`unit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'symbol' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'nameEN' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'barcode' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'is_mezan' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'group_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'unit' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_Deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'purch_price' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'purchdollar' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'sale_price' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'saledollar' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'sale_reb7' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'limit' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tax' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'discount' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tax_group' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'minprice' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'group2' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'wholesale' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'offersale' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'whole_price' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'G_reb7' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'offer_price' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'D_reb7' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'type' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'no' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'ref_no' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'ref_vendor' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'ref_price' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'ref_quan' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'outoffer' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'stock_address' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'i1' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'i2' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'imge' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'web_sku' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'web_item_category_id' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'chk_fast_menu' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'item_orders' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'stock' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'slug' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'offer_until' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'cust_groups' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `cust_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => 'auto_increment',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'customers' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `country` int(11) DEFAULT NULL,
  `city` int(11) DEFAULT NULL,
  `area` int(11) DEFAULT NULL,
  `act` int(11) DEFAULT NULL,
  `national_id` text DEFAULT NULL,
  `tel` text DEFAULT NULL,
  `mobile` text DEFAULT NULL,
  `fax` text DEFAULT NULL,
  `email` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `IS_Deleted` tinyint(1) DEFAULT NULL,
  `maxdepit` double DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `tax_no` text DEFAULT NULL,
  `sec` tinyint(1) DEFAULT NULL,
  `discount_balance` double DEFAULT NULL,
  `type_s` int(11) DEFAULT NULL,
  `branch` int(11) DEFAULT NULL,
  `imge` text DEFAULT NULL,
  `nation` text DEFAULT NULL,
  `accountNo` bigint(20) DEFAULT NULL,
  `mobile2` text DEFAULT NULL,
  `mobile3` text DEFAULT NULL,
  `mobile4` text DEFAULT NULL,
  `mobile5` text DEFAULT NULL,
  `Address1` text DEFAULT NULL,
  `Address2` text DEFAULT NULL,
  `Address3` text DEFAULT NULL,
  `cust_user` text DEFAULT NULL,
  `cust_pass` text DEFAULT NULL,
  `delevry_costs` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'country' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'city' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'area' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'act' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'national_id' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tel' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'mobile' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'fax' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'email' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_Deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'maxdepit' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'type' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tax_no' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'sec' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'discount_balance' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'type_s' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'branch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'imge' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'nation' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'accountNo' => 
        array (
          'type' => 'bigint(20)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'mobile2' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'mobile3' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'mobile4' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'mobile5' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Address1' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Address2' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Address3' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'cust_user' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'cust_pass' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'delevry_costs' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'days' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `days` (
  `id` int(11) DEFAULT NULL,
  `wardya_no` int(11) DEFAULT NULL,
  `date_open` datetime DEFAULT NULL,
  `user_open` int(11) DEFAULT NULL,
  `date_close` datetime DEFAULT NULL,
  `user_close` int(11) DEFAULT NULL,
  `IS_open` tinyint(1) DEFAULT NULL,
  `net` double DEFAULT NULL,
  `branch` int(11) DEFAULT NULL,
  `day_no` int(11) DEFAULT NULL,
  `pc_open` text DEFAULT NULL,
  `pc_close` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'wardya_no' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date_open' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'user_open' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date_close' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'user_close' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_open' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'net' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'branch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'day_no' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'pc_open' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'pc_close' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'delivery_emps' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `delivery_emps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text DEFAULT NULL,
  `tel` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => 'auto_increment',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tel' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'address' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'is_deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => '0',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'departments' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `departments` (
  `id` int(11) DEFAULT NULL,
  `manag_id` int(11) DEFAULT NULL,
  `name` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'manag_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'employees' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `employees` (
  `id` int(11) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `manag` int(11) DEFAULT NULL,
  `dep` int(11) DEFAULT NULL,
  `state` int(11) DEFAULT NULL,
  `job` int(11) DEFAULT NULL,
  `branch` int(11) DEFAULT NULL,
  `birth_date` datetime DEFAULT NULL,
  `insurance_no` int(11) DEFAULT NULL,
  `work_date` datetime DEFAULT NULL,
  `marital_state` int(11) DEFAULT NULL,
  `nationality` int(11) DEFAULT NULL,
  `sex` text DEFAULT NULL,
  `tel` text DEFAULT NULL,
  `mobile` text DEFAULT NULL,
  `email` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `NatO` int(11) DEFAULT NULL,
  `NatOI` text DEFAULT NULL,
  `NatODateS` datetime DEFAULT NULL,
  `NatODateE` datetime DEFAULT NULL,
  `NatT` int(11) DEFAULT NULL,
  `NatTI` text DEFAULT NULL,
  `NatTDateS` datetime DEFAULT NULL,
  `NatTDateE` datetime DEFAULT NULL,
  `StudO` text DEFAULT NULL,
  `StudOL` int(11) DEFAULT NULL,
  `StudT` text DEFAULT NULL,
  `StudTL` int(11) DEFAULT NULL,
  `FBCode` int(11) DEFAULT NULL,
  `FbRoll` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `salary_basic` double DEFAULT NULL,
  `salary_add` double DEFAULT NULL,
  `salary_other` double DEFAULT NULL,
  `house` double DEFAULT NULL,
  `food` double DEFAULT NULL,
  `travel` double DEFAULT NULL,
  `medical` double DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `IS_Deleted` tinyint(1) DEFAULT NULL,
  `Sallary_Roll` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'manag' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'dep' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'state' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'job' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'branch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'birth_date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'insurance_no' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'work_date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'marital_state' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'nationality' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'sex' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tel' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'mobile' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'email' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'address' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'NatO' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'NatOI' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'NatODateS' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'NatODateE' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'NatT' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'NatTI' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'NatTDateS' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'NatTDateE' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'StudO' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'StudOL' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'StudT' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'StudTL' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'FBCode' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'FbRoll' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'salary_basic' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'salary_add' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'salary_other' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'house' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'food' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'travel' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'medical' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'CreatedBy' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_Deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Sallary_Roll' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'eo_item_img' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `eo_item_img` (
  `id` int(11) NOT NULL,
  `path` varchar(255) NOT NULL DEFAULT \'\',
  `url` varchar(255) NOT NULL DEFAULT \'\',
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'path' => 
        array (
          'type' => 'varchar(255)',
          'null' => 'NO',
          'default' => '\'\'',
          'extra' => '',
          'gen' => NULL,
        ),
        'url' => 
        array (
          'type' => 'varchar(255)',
          'null' => 'NO',
          'default' => '\'\'',
          'extra' => '',
          'gen' => NULL,
        ),
        'updated' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'gard' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `gard` (
  `id` int(11) NOT NULL,
  `date` datetime DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `emp_name` text DEFAULT NULL,
  `emp_id` int(11) DEFAULT NULL,
  `branch` int(11) DEFAULT NULL,
  `tot_quntity` int(11) DEFAULT NULL,
  `tot_items` int(11) DEFAULT NULL,
  `tot_sale` int(11) DEFAULT NULL,
  `tot_purch` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT NULL,
  `safe` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'user_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'emp_name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'emp_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'branch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tot_quntity' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tot_items' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tot_sale' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tot_purch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'is_deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'safe' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'gard_sub' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `gard_sub` (
  `sub_id` int(11) DEFAULT NULL,
  `GARD_id` int(11) DEFAULT NULL,
  `code` text DEFAULT NULL,
  `barcode` text DEFAULT NULL,
  `item_name` text DEFAULT NULL,
  `unit_name` text DEFAULT NULL,
  `group_name` text DEFAULT NULL,
  `purch` int(11) DEFAULT NULL,
  `purchD` int(11) DEFAULT NULL,
  `purchLD` text DEFAULT NULL,
  `Sale` int(11) DEFAULT NULL,
  `SaleD` int(11) DEFAULT NULL,
  `saleLD` text DEFAULT NULL,
  `mezan` tinyint(1) DEFAULT NULL,
  `quintity` int(11) DEFAULT NULL,
  `quintityD` text DEFAULT NULL,
  `tot_sale` int(11) DEFAULT NULL,
  `tot_purch` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `sys_Qty` int(11) DEFAULT NULL,
  `settlement` int(11) DEFAULT NULL,
  `adds` tinyint(1) DEFAULT NULL,
  `settlement_done` tinyint(1) DEFAULT NULL,
  KEY `ix_gs_gard` (`GARD_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'sub_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'GARD_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'code' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'barcode' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'item_name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'unit_name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'group_name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'purch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'purchD' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'purchLD' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Sale' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'SaleD' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'saleLD' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'mezan' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'quintity' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'quintityD' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tot_sale' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tot_purch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'sys_Qty' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'settlement' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'adds' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'settlement_done' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'groups' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `groups` (
  `id` int(11) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `nameEN` text DEFAULT NULL,
  `color` int(11) DEFAULT NULL,
  `printer` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `dis` int(11) DEFAULT NULL,
  `main_groups` int(11) DEFAULT NULL,
  `PACKUP_Printers` text DEFAULT NULL,
  `printer_count` int(11) DEFAULT NULL,
  `PACKUP_Printers_count` int(11) DEFAULT NULL,
  `web_id` text DEFAULT NULL,
  `web_cat_parent_id` text DEFAULT NULL,
  `group_orders` int(11) DEFAULT NULL,
  `image_path` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'nameEN' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'color' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'printer' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'parent_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'dis' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'main_groups' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'PACKUP_Printers' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'printer_count' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'PACKUP_Printers_count' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'web_id' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'web_cat_parent_id' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'group_orders' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'image_path' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'inv' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `inv` (
  `proc_id` int(11) DEFAULT NULL,
  `proc_type` int(11) DEFAULT NULL,
  `id` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `inv_type` int(11) DEFAULT NULL,
  `safe` int(11) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `cust_id` int(11) DEFAULT NULL,
  `sales_emp` int(11) DEFAULT NULL,
  `tot_purch` double DEFAULT NULL,
  `tot_sale` double DEFAULT NULL,
  `tot_net` double DEFAULT NULL,
  `purch_rest_id` int(11) DEFAULT NULL,
  `sale_rest_id` int(11) DEFAULT NULL,
  `InvId_Return` int(11) DEFAULT NULL,
  `branch` int(11) DEFAULT NULL,
  `IS_Buy` tinyint(1) DEFAULT NULL,
  `IS_Deleted` tinyint(1) DEFAULT NULL,
  `user_IS_Deleted` int(11) DEFAULT NULL,
  `minus` double DEFAULT NULL,
  `paid` double DEFAULT NULL,
  `salesman` int(11) DEFAULT NULL,
  `tax` double DEFAULT NULL,
  `pay_type` int(11) DEFAULT NULL,
  `bank` bigint(20) DEFAULT NULL,
  `tax_ats` double DEFAULT NULL,
  `disc` double DEFAULT NULL,
  `refno` text DEFAULT NULL,
  `cust_disc` double DEFAULT NULL,
  `custdiscadd` double DEFAULT NULL,
  `rest_type` int(11) DEFAULT NULL,
  `tawla` int(11) DEFAULT NULL,
  `delivery` int(11) DEFAULT NULL,
  `reserv_custname` text DEFAULT NULL,
  `reserv_custtel` text DEFAULT NULL,
  `reserv_custtel2` text DEFAULT NULL,
  `reserv_custadd` text DEFAULT NULL,
  `reserv_date` datetime DEFAULT NULL,
  `reserv_notes` text DEFAULT NULL,
  `mobapp_id` int(11) DEFAULT NULL,
  `fees` double DEFAULT NULL,
  `client_type` int(11) DEFAULT NULL,
  `client_name` text DEFAULT NULL,
  `client_mobile` text DEFAULT NULL,
  `costadd` double DEFAULT NULL,
  `is_tfseel` tinyint(1) DEFAULT NULL,
  `is_tfseelrec` int(11) DEFAULT NULL,
  `expense1` double DEFAULT NULL,
  `expense2` double DEFAULT NULL,
  `expense3` double DEFAULT NULL,
  `expense4` double DEFAULT NULL,
  `expense5` double DEFAULT NULL,
  `totexpense` double DEFAULT NULL,
  `expenseacc1` int(11) DEFAULT NULL,
  `expenseacc2` int(11) DEFAULT NULL,
  `expenseacc3` int(11) DEFAULT NULL,
  `expenseacc4` int(11) DEFAULT NULL,
  `expenseacc5` int(11) DEFAULT NULL,
  `offer_id` int(11) DEFAULT NULL,
  `tawla_paid` tinyint(1) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_mat` tinyint(1) DEFAULT NULL,
  `is_manfc` tinyint(1) DEFAULT NULL,
  `is_complete` tinyint(1) DEFAULT NULL,
  `network_type` int(11) DEFAULT NULL,
  `cash_part` double DEFAULT NULL,
  `network_part` double DEFAULT NULL,
  `center` int(11) DEFAULT NULL,
  `agel_date` datetime DEFAULT NULL,
  `agel_part` double DEFAULT NULL,
  `bank_part` double DEFAULT NULL,
  `bank2` int(11) DEFAULT NULL,
  `check_refno` text DEFAULT NULL,
  `emp_part` double DEFAULT NULL,
  `emp` int(11) DEFAULT NULL,
  `tab3tax` double DEFAULT NULL,
  `IS_updated` tinyint(1) DEFAULT NULL,
  `user_IS_updated` int(11) DEFAULT NULL,
  `IS_Temp` tinyint(1) DEFAULT NULL,
  `dlevman` int(11) DEFAULT NULL,
  `kiychen_done` tinyint(1) DEFAULT NULL,
  `rest_no_day` int(11) DEFAULT NULL,
  `coboun` tinyint(1) DEFAULT NULL,
  `coboun_id` int(11) DEFAULT NULL,
  `coboun_Dis` double DEFAULT NULL,
  `date_delevGoes` datetime DEFAULT NULL,
  `date_delevCome` datetime DEFAULT NULL,
  `date_come` datetime DEFAULT NULL,
  `date_go` datetime DEFAULT NULL,
  `voucher_code` text DEFAULT NULL,
  `voucher_value` int(11) DEFAULT NULL,
  `pc` text DEFAULT NULL,
  `printed_recipt` datetime DEFAULT NULL,
  `persons` int(11) DEFAULT NULL,
  `inv_notes` text DEFAULT NULL,
  KEY `ix_inv_dd` (`date`),
  KEY `ix_inv_tp` (`IS_Deleted`,`IS_Buy`,`proc_type`),
  KEY `ix_inv_proc` (`proc_id`),
  KEY `ix_inv_id` (`id`),
  KEY `ix_inv_cust` (`cust_id`),
  KEY `ix_inv_sm` (`salesman`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'proc_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'proc_type' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'inv_type' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'safe' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'stock' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'cust_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'sales_emp' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tot_purch' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tot_sale' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tot_net' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'purch_rest_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'sale_rest_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'InvId_Return' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'branch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_Buy' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_Deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'user_IS_Deleted' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'minus' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'paid' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'salesman' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tax' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'pay_type' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'bank' => 
        array (
          'type' => 'bigint(20)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tax_ats' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'disc' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'refno' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'cust_disc' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'custdiscadd' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'rest_type' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tawla' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'delivery' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'reserv_custname' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'reserv_custtel' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'reserv_custtel2' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'reserv_custadd' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'reserv_date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'reserv_notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'mobapp_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'fees' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'client_type' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'client_name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'client_mobile' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'costadd' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'is_tfseel' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'is_tfseelrec' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'expense1' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'expense2' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'expense3' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'expense4' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'expense5' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'totexpense' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'expenseacc1' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'expenseacc2' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'expenseacc3' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'expenseacc4' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'expenseacc5' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'offer_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tawla_paid' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'is_mat' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'is_manfc' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'is_complete' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'network_type' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'cash_part' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'network_part' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'center' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'agel_date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'agel_part' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'bank_part' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'bank2' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'check_refno' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'emp_part' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'emp' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tab3tax' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_updated' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'user_IS_updated' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_Temp' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'dlevman' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'kiychen_done' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'rest_no_day' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'coboun' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'coboun_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'coboun_Dis' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date_delevGoes' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date_delevCome' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date_come' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date_go' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'voucher_code' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'voucher_value' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'pc' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'printed_recipt' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'persons' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'inv_notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'inv_sub' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `inv_sub` (
  `id` int(11) DEFAULT NULL,
  `proc_id` int(11) DEFAULT NULL,
  `proc_type` int(11) DEFAULT NULL,
  `currency_from` int(11) DEFAULT NULL,
  `unit` int(11) DEFAULT NULL,
  `i1` bigint(20) DEFAULT NULL,
  `i2` bigint(20) DEFAULT NULL,
  `val` double DEFAULT NULL,
  `val1` double DEFAULT NULL,
  `exchange_price` double DEFAULT NULL,
  `expire_date` datetime DEFAULT NULL,
  `taxperc` double DEFAULT NULL,
  `taxval` double DEFAULT NULL,
  `discount` double DEFAULT NULL,
  `expense` double DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `Txtr` text DEFAULT NULL,
  `kiychen_printed` tinyint(1) DEFAULT NULL,
  `invpurch` text DEFAULT NULL,
  `barcode` text DEFAULT NULL,
  `sale_price` int(11) DEFAULT NULL,
  `kiychen_ok` tinyint(1) DEFAULT NULL,
  `kiychen_no` tinyint(1) DEFAULT NULL,
  `kiychen_done` tinyint(1) DEFAULT NULL,
  `kiychen_goes` tinyint(1) DEFAULT NULL,
  `kiychen_note` text DEFAULT NULL,
  `date_goes` datetime DEFAULT NULL,
  `date_date_Done` datetime DEFAULT NULL,
  KEY `ix_sub_cur` (`currency_from`),
  KEY `ix_sub_proc` (`proc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'proc_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'proc_type' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'currency_from' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'unit' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'i1' => 
        array (
          'type' => 'bigint(20)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'i2' => 
        array (
          'type' => 'bigint(20)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'val' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'val1' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'exchange_price' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'expire_date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'taxperc' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'taxval' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'discount' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'expense' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Txtr' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'kiychen_printed' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'invpurch' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'barcode' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'sale_price' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'kiychen_ok' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'kiychen_no' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'kiychen_done' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'kiychen_goes' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'kiychen_note' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date_goes' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date_date_Done' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'item_bal' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `item_bal` (
  `id` int(11) NOT NULL,
  `bal` double NOT NULL DEFAULT 0,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'bal' => 
        array (
          'type' => 'double',
          'null' => 'NO',
          'default' => '0',
          'extra' => '',
          'gen' => NULL,
        ),
        'updated' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'loyalty' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `loyalty` (
  `cust_id` int(11) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `total_earned` int(11) NOT NULL DEFAULT 0,
  `total_redeemed` int(11) NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`cust_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'cust_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'points' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => '0',
          'extra' => '',
          'gen' => NULL,
        ),
        'total_earned' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => '0',
          'extra' => '',
          'gen' => NULL,
        ),
        'total_redeemed' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => '0',
          'extra' => '',
          'gen' => NULL,
        ),
        'updated_at' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'current_timestamp()',
          'extra' => 'on update current_timestamp()',
          'gen' => NULL,
        ),
      ),
    ),
    'loyalty_log' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `loyalty_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cust_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `type` enum(\'earn\',\'redeem\',\'adjust\') NOT NULL,
  `inv_id` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cust` (`cust_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => 'auto_increment',
          'gen' => NULL,
        ),
        'cust_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'points' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'type' => 
        array (
          'type' => 'enum(\'earn\',\'redeem\',\'adjust\')',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'inv_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'note' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'created_at' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'current_timestamp()',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'pay_methods' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `pay_methods` (
  `id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'varchar(64)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'active' => 
        array (
          'type' => 'tinyint(4)',
          'null' => 'NO',
          'default' => '1',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'restrictions' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `restrictions` (
  `id` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `doc_no` int(11) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `state` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `branch` int(11) DEFAULT NULL,
  `IS_Deleted` tinyint(1) DEFAULT NULL,
  `omla` int(11) DEFAULT NULL,
  `omlaval` double DEFAULT NULL,
  KEY `ix_r_dt` (`date`),
  KEY `ix_r_id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'doc_no' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'type' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'state' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'branch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_Deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'omla' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'omlaval' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'restrictions_sub' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `restrictions_sub` (
  `res_id` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `dept` double DEFAULT NULL,
  `credit` double DEFAULT NULL,
  `acc_no` bigint(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `branch` int(11) DEFAULT NULL,
  `omla` int(11) DEFAULT NULL,
  `omlaval` double DEFAULT NULL,
  `center` int(11) DEFAULT NULL,
  KEY `ix_rs_res` (`res_id`),
  KEY `ix_rs_acc` (`acc_no`),
  KEY `ix_rs_dt` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'res_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'dept' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'credit' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'acc_no' => 
        array (
          'type' => 'bigint(20)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'branch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'omla' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'omlaval' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'center' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'rs_count' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `rs_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` int(11) NOT NULL,
  `counted` double NOT NULL DEFAULT 0,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => 'auto_increment',
          'gen' => NULL,
        ),
        'code' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'counted' => 
        array (
          'type' => 'double',
          'null' => 'NO',
          'default' => '0',
          'extra' => '',
          'gen' => NULL,
        ),
        'created' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'current_timestamp()',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'rs_eo_log' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `rs_eo_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dt` datetime DEFAULT current_timestamp(),
  `rs_id` int(11) DEFAULT NULL,
  `eo_id` int(11) DEFAULT NULL,
  `qty` double DEFAULT NULL,
  `note` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => 'auto_increment',
          'gen' => NULL,
        ),
        'dt' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'current_timestamp()',
          'extra' => '',
          'gen' => NULL,
        ),
        'rs_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'eo_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'qty' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'note' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'rs_eo_map' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `rs_eo_map` (
  `rs_id` int(11) NOT NULL,
  `eo_id` int(11) NOT NULL,
  `slug` text DEFAULT NULL,
  PRIMARY KEY (`rs_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'rs_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'eo_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'slug' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'rs_expenses' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `rs_expenses` (
  `code` int(11) NOT NULL,
  `name` text DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'code' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'rs_safebox' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `rs_safebox` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `safe_id` int(11) NOT NULL,
  `dt` date NOT NULL,
  `kind` varchar(8) NOT NULL,
  `amount` double NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `ref_no` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => 'auto_increment',
          'gen' => NULL,
        ),
        'safe_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'dt' => 
        array (
          'type' => 'date',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'kind' => 
        array (
          'type' => 'varchar(8)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'amount' => 
        array (
          'type' => 'double',
          'null' => 'NO',
          'default' => '0',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'ref_no' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => '0',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'rs_seq' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `rs_seq` (
  `name` varchar(32) NOT NULL,
  `val` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'name' => 
        array (
          'type' => 'varchar(32)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'val' => 
        array (
          'type' => 'bigint(20)',
          'null' => 'NO',
          'default' => '0',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'rs_sync' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `rs_sync` (
  `k` varchar(64) NOT NULL,
  `v` text DEFAULT NULL,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'k' => 
        array (
          'type' => 'varchar(64)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'v' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'safe_emps' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `safe_emps` (
  `safe_id` int(11) DEFAULT NULL,
  `emp_id` int(11) DEFAULT NULL,
  `IS_deleted` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'safe_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'emp_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'safes' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `safes` (
  `id` int(11) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `branch` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `IS_Default` tinyint(1) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `IS_Deleted` tinyint(1) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `tel` text DEFAULT NULL,
  `showdata` tinyint(1) DEFAULT NULL,
  `mob` text DEFAULT NULL,
  `accountNo` bigint(20) DEFAULT NULL,
  `online` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'branch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'status' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_Default' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_Deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'address' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tel' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'showdata' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'mob' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'accountNo' => 
        array (
          'type' => 'bigint(20)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'online' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'salarypay' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `salarypay` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `salary_net` double NOT NULL DEFAULT 0,
  `date` datetime DEFAULT NULL,
  `emp_resp` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_emp_my` (`emp`,`month`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => 'auto_increment',
          'gen' => NULL,
        ),
        'emp' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'month' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'year' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'salary_net' => 
        array (
          'type' => 'double',
          'null' => 'NO',
          'default' => '0',
          'extra' => '',
          'gen' => NULL,
        ),
        'date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'emp_resp' => 
        array (
          'type' => 'varchar(64)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'salesmen' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `salesmen` (
  `id` int(11) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `comm` double DEFAULT NULL,
  `tel` text DEFAULT NULL,
  `mobile` text DEFAULT NULL,
  `email` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `dlev` tinyint(1) DEFAULT NULL,
  `IS_Deleted` tinyint(1) DEFAULT NULL,
  `IS_EMP` tinyint(1) DEFAULT NULL,
  `emp_id` int(11) DEFAULT NULL,
  `branch` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'comm' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tel' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'mobile' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'email' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'dlev' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_Deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_EMP' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'emp_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'branch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'sandd' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `sandd` (
  `id` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `emp_id` int(11) DEFAULT NULL,
  `cust_id` int(11) DEFAULT NULL,
  `val` double DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `safe_bank_id` int(11) DEFAULT NULL,
  `sales_emp` int(11) DEFAULT NULL,
  `check_no` text DEFAULT NULL,
  `check_date` datetime DEFAULT NULL,
  `checkbank` text DEFAULT NULL,
  `check_state` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `rest_id` int(11) DEFAULT NULL,
  `branch` int(11) DEFAULT NULL,
  `IS_Deleted` tinyint(1) DEFAULT NULL,
  `proc_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'emp_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'cust_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'val' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'type' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'safe_bank_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'sales_emp' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'check_no' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'check_date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'checkbank' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'check_state' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'rest_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'branch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_Deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'proc_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'sandq' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `sandq` (
  `id` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `cust_id` int(11) DEFAULT NULL,
  `val` double DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `safe_bank_id` int(11) DEFAULT NULL,
  `sales_emp` int(11) DEFAULT NULL,
  `check_no` text DEFAULT NULL,
  `check_date` datetime DEFAULT NULL,
  `checkbank` text DEFAULT NULL,
  `check_state` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `rest_id` int(11) DEFAULT NULL,
  `branch` int(11) DEFAULT NULL,
  `IS_Deleted` tinyint(1) DEFAULT NULL,
  `is_installment` tinyint(1) DEFAULT NULL,
  `installment_date` datetime DEFAULT NULL,
  `proc_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'cust_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'val' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'type' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'safe_bank_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'sales_emp' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'check_no' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'check_date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'checkbank' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'check_state' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'rest_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'branch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_Deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'is_installment' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'installment_date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'proc_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'sandqd' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `sandqd` (
  `id` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `emp_person` int(11) DEFAULT NULL,
  `cust_id` int(11) DEFAULT NULL,
  `acc_code` bigint(20) DEFAULT NULL,
  `val` double DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `safe_bank_id` bigint(20) DEFAULT NULL,
  `sales_emp` int(11) DEFAULT NULL,
  `check_no` text DEFAULT NULL,
  `check_date` datetime DEFAULT NULL,
  `checkbank` text DEFAULT NULL,
  `check_state` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `rest_id` int(11) DEFAULT NULL,
  `branch` int(11) DEFAULT NULL,
  `IS_Deleted` tinyint(1) DEFAULT NULL,
  `center` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'emp_person' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'cust_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'acc_code' => 
        array (
          'type' => 'bigint(20)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'val' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'type' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'safe_bank_id' => 
        array (
          'type' => 'bigint(20)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'sales_emp' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'check_no' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'check_date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'checkbank' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'check_state' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'rest_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'branch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_Deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'center' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'sandsd' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `sandsd` (
  `id` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `emp_person` int(11) DEFAULT NULL,
  `emp_id` int(11) DEFAULT NULL,
  `acc_code` bigint(20) DEFAULT NULL,
  `val` double DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `safe_bank_id` bigint(20) DEFAULT NULL,
  `sales_emp` int(11) DEFAULT NULL,
  `check_no` text DEFAULT NULL,
  `check_date` datetime DEFAULT NULL,
  `checkbank` text DEFAULT NULL,
  `check_state` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `rest_id` int(11) DEFAULT NULL,
  `branch` int(11) DEFAULT NULL,
  `IS_Deleted` tinyint(1) DEFAULT NULL,
  `center` int(11) DEFAULT NULL,
  `typesanad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'emp_person' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'emp_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'acc_code' => 
        array (
          'type' => 'bigint(20)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'val' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'type' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'safe_bank_id' => 
        array (
          'type' => 'bigint(20)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'sales_emp' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'check_no' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'check_date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'checkbank' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'check_state' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'rest_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'branch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_Deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'center' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'typesanad' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'stock_emps' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `stock_emps` (
  `stock_id` int(11) DEFAULT NULL,
  `emp_id` int(11) DEFAULT NULL,
  `IS_deleted` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'stock_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'emp_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'stocks' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `stocks` (
  `id` int(11) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `branch` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `IS_Default` tinyint(1) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `IS_Deleted` tinyint(1) DEFAULT NULL,
  `accountNo` bigint(20) DEFAULT NULL,
  `online` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'branch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'status' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_Default' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_Deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'accountNo' => 
        array (
          'type' => 'bigint(20)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'online' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'tawla' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `tawla` (
  `id` int(11) DEFAULT NULL,
  `tawla_group` text DEFAULT NULL,
  `name` text DEFAULT NULL,
  `open_price_h` int(11) DEFAULT NULL,
  `open_price_m` int(11) DEFAULT NULL,
  `model` text DEFAULT NULL,
  `details1` text DEFAULT NULL,
  `details2` text DEFAULT NULL,
  `details3` text DEFAULT NULL,
  `details4` text DEFAULT NULL,
  `details5` text DEFAULT NULL,
  `details6` text DEFAULT NULL,
  `details7` text DEFAULT NULL,
  `details8` text DEFAULT NULL,
  `details9` text DEFAULT NULL,
  `chk_location` tinyint(1) DEFAULT NULL,
  `x` int(11) DEFAULT NULL,
  `y` int(11) DEFAULT NULL,
  `H` int(11) DEFAULT NULL,
  `w` int(11) DEFAULT NULL,
  `Person` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'tawla_group' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'open_price_h' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'open_price_m' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'model' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'details1' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'details2' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'details3' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'details4' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'details5' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'details6' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'details7' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'details8' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'details9' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'chk_location' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'x' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'y' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'H' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'w' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Person' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'tawla_groups' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `tawla_groups` (
  `id` int(11) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `type` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'type' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'twreed_srf' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `twreed_srf` (
  `id` int(11) DEFAULT NULL,
  `no` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `safe` int(11) DEFAULT NULL,
  `refno` text DEFAULT NULL,
  `branch` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `Gard_id` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'no' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'user_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'type' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'safe' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'refno' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'branch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'notes' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'Gard_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'is_deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'twreed_srf_sub' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `twreed_srf_sub` (
  `proc_id` int(11) DEFAULT NULL,
  `item` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `quan` double DEFAULT NULL,
  `totquan` double DEFAULT NULL,
  `price` double DEFAULT NULL,
  `sum` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'proc_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'item' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'group_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'quan' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'totquan' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'price' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'sum' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'units' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `units` (
  `id` int(11) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `count` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'count' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'user_perms' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `user_perms` (
  `user_id` int(11) NOT NULL,
  `perm` varchar(64) NOT NULL,
  `val` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`,`perm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'user_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'perm' => 
        array (
          'type' => 'varchar(64)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'val' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'NO',
          'default' => '0',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'users' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL,
  `emp` int(11) DEFAULT NULL,
  `username` text DEFAULT NULL,
  `pwd` text DEFAULT NULL,
  `edit_inv` tinyint(1) DEFAULT NULL,
  `IS_Deleted` tinyint(1) DEFAULT NULL,
  `edit_disc` tinyint(1) DEFAULT NULL,
  `edit_price` tinyint(1) DEFAULT NULL,
  `edit_date` tinyint(1) DEFAULT NULL,
  `item_disc` double DEFAULT NULL,
  `inv_disc` double DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'NO',
          'default' => NULL,
          'extra' => '',
          'gen' => NULL,
        ),
        'emp' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'username' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'pwd' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'edit_inv' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_Deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'edit_disc' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'edit_price' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'edit_date' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'item_disc' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'inv_disc' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'voucher' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `voucher` (
  `id` int(11) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `code` text DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `used` int(11) DEFAULT NULL,
  `date_create` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `value` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT NULL,
  `vaild` tinyint(1) DEFAULT NULL,
  `design` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT NULL,
  `user_deleted` int(11) DEFAULT NULL,
  `date_deleted` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'code' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'type' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'used' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date_create' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date_end' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'value' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'used_count' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'vaild' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'design' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'is_deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'user_deleted' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date_deleted' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'voucher_cust' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `voucher_cust` (
  `id` int(11) DEFAULT NULL,
  `cust_id` int(11) DEFAULT NULL,
  `cust_name` text DEFAULT NULL,
  `cust_mob` text DEFAULT NULL,
  `voucher_id` int(11) DEFAULT NULL,
  `c_date_create` date DEFAULT NULL,
  `c_date_end` date DEFAULT NULL,
  `c_value` int(11) DEFAULT NULL,
  `vaild` tinyint(1) DEFAULT NULL,
  `design` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT NULL,
  `c_used` int(11) DEFAULT NULL,
  `c_used_Count` int(11) DEFAULT NULL,
  `cust_voucher_id` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'cust_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'cust_name' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'cust_mob' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'voucher_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'c_date_create' => 
        array (
          'type' => 'date',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'c_date_end' => 
        array (
          'type' => 'date',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'c_value' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'vaild' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'design' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'is_deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'c_used' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'c_used_Count' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'cust_voucher_id' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'voucher_cust_invs' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `voucher_cust_invs` (
  `id` int(11) DEFAULT NULL,
  `cust_voucher_id` text DEFAULT NULL,
  `inv_invproc` int(11) DEFAULT NULL,
  `inv_date` datetime DEFAULT NULL,
  `inv_tot` int(11) DEFAULT NULL,
  `inv_totVoucher` int(11) DEFAULT NULL,
  `user_create` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT NULL,
  `deleted_date` datetime DEFAULT NULL,
  `user_deleted_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'cust_voucher_id' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'inv_invproc' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'inv_date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'inv_tot' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'inv_totVoucher' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'user_create' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'is_deleted' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'deleted_date' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'user_deleted_id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
    'wardya' => 
    array (
      'ddl' => 'CREATE TABLE IF NOT EXISTS `wardya` (
  `id` int(11) DEFAULT NULL,
  `wardya_no` int(11) DEFAULT NULL,
  `date_open` datetime DEFAULT NULL,
  `user_open` int(11) DEFAULT NULL,
  `date_close` datetime DEFAULT NULL,
  `user_close` int(11) DEFAULT NULL,
  `IS_open` tinyint(1) DEFAULT NULL,
  `net` double DEFAULT NULL,
  `day_no` int(11) DEFAULT NULL,
  `branch` int(11) DEFAULT NULL,
  `close_remotly` tinyint(1) DEFAULT NULL,
  `pc_open` text DEFAULT NULL,
  `pc_close` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
      'cols' => 
      array (
        'id' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'wardya_no' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date_open' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'user_open' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'date_close' => 
        array (
          'type' => 'datetime',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'user_close' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'IS_open' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'net' => 
        array (
          'type' => 'double',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'day_no' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'branch' => 
        array (
          'type' => 'int(11)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'close_remotly' => 
        array (
          'type' => 'tinyint(1)',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'pc_open' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
        'pc_close' => 
        array (
          'type' => 'text',
          'null' => 'YES',
          'default' => 'NULL',
          'extra' => '',
          'gen' => NULL,
        ),
      ),
    ),
  ),
);
