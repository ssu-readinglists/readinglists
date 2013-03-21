<?php  //$Id: upgrade.php,v 1.4 2010/03/18 16:04:49 jp5987 Exp $

// This file keeps track of upgrades to
// the refworks module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_refworks_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

/// And upgrade begins here. For each one, you'll need one
/// block of code similar to the next one. Please, delete
/// this comment lines once this file start handling proper
/// upgrade code.

/// if ($result && $oldversion < YYYYMMDD00) { //New version in version.php
///     $result = result of "/lib/ddllib.php" function calls
/// }

/// Lines below (this included)  MUST BE DELETED once you get the first version
/// of your module ready to be installed. They are here only
/// for demonstrative purposes and to show how the refworks
/// iself has been upgraded.

/// For each upgrade block, the file refworks/version.php
/// needs to be updated . Such change allows Moodle to know
/// that this file has to be processed.

/// To know more about how to write correct DB upgrade scripts it's
/// highly recommended to read information available at:
///   http://docs.moodle.org/en/Development:XMLDB_Documentation
/// and to play with the XMLDB Editor (in the admin menu) and its
/// PHP generation posibilities.



/// And that's all. Please, examine and understand the 3 example blocks above. Also
/// it's interesting to look how other modules are using this script. Remember that
/// the basic idea is to have "blocks" of code (each one being executed only once,
/// when the module version (version.php) is updated.

/// Lines above (this included) MUST BE DELETED once you get the first version of
/// yout module working. Each time you need to modify something in the module (DB
/// related, you'll raise the version and add one upgrade block here.

       if ($result && $oldversion < 2009102900) {

    /// Define table refworks_collab_accs to be created
        $table = new XMLDBTable('refworks_collab_accs');

    /// Adding fields to table refworks_collab_accs
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
        $table->addFieldInfo('login', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
        $table->addFieldInfo('password', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, 'Pa$$w0rd');
        $table->addFieldInfo('created', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('lastlogin', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);

    /// Adding keys to table refworks_collab_accs
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for refworks_collab_accs
        $result = $result && create_table($table);

        /// Define table refworks_collab_users to be created
        $table = new XMLDBTable('refworks_collab_users');

    /// Adding fields to table refworks_collab_users
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('accid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('owner', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

    /// Adding keys to table refworks_collab_users
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addKeyInfo('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->addKeyInfo('accid', XMLDB_KEY_FOREIGN, array('accid'), 'refworks_collab_accs', array('id'));

    /// Launch create table for refworks_collab_users
        $result = $result && create_table($table);

    }
    if ($result && $oldversion < 2009111301) {

    /// Define field datedeleted to be added to refworks_collab_accs
        $table = new XMLDBTable('refworks_collab_accs');
        $field = new XMLDBField('datedeleted');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'lastlogin');

    /// Launch add field datedeleted
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2009111304) {

    /// Define field autopopfolder to be added to refworks
        $table = new XMLDBTable('refworks');
        $field = new XMLDBField('autopopfolder');
        $field->setAttributes(XMLDB_TYPE_CHAR, '26', null, XMLDB_NOTNULL, null, null, null, null, 'timemodified');

    /// Launch add field autopopfolder
        $result = $result && add_field($table, $field);

        /// Define field autopopdata to be added to refworks
        $table = new XMLDBTable('refworks');
        $field = new XMLDBField('autopopdata');
        $field->setAttributes(XMLDB_TYPE_TEXT, 'big', null, null, null, null, null, null, 'autopopfolder');

    /// Launch add field autopopdata
        $result = $result && add_field($table, $field);
    }

/// Final return of upgrade result (true/false) to Moodle. Must be
/// always the last line in the script
    return $result;
}

?>
