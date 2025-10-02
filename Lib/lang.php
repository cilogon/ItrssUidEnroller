<?php
/**
 * COmanage Registry ITRSS Uid Enroller Language File
 *
 */

global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_itrss_uid_enroller_texts['en_US'] = array(

  //Error messages
  'er.iue.no_email_attribute'	=> 'Unable to create a custom ITRSS uid due to a missing email address in the Petition Attributes.',
  'er.iue.bad_email_address'	=> 'Unable to create a custom ITRSS uid due to a poorly formed email address in the Petition Attributes: %1$s',
  'er.iue.too_many_collisions'	=> 'Unable to create a custom ITRSS uid due to too many collisions.',
  'er.iue.no_delete'		=> 'Unable to create a custom ITRSS uid. Unable to delete the current uid on the Co Person.',
  'er.iue.cant_save'		=> 'Unable to save the new custom ITRSS uid.',

  //Infrormational
  'in.iue.plugin_exception_finalize'	=> 'There was an exception in the ITRSS Uid Enroller Plugin while finalizing the petition.',
);
