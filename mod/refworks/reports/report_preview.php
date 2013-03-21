<?php
/**
 * Report that will give an indication of how the references will appear in a variety of formats
 *
 * @copyright &copy; 2010 The Open University
 * @author j.platts@open.ac.uk
 * @author j.ackland-snow@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks/reports/
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/local/references/linking.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/local/references/convert/refxml.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/local/references/apibib/apibib_lib.php');


class refworks_report_preview extends refworks_report{

    public static function run_report($data) {

        //turn data string into an xml dom object
        if (!$refxml = self::string_to_dom($data)) {
            return;
        }

        $reporttype = required_param('preview_type',PARAM_TEXT);

        echo '<div class="report">';

        if ($reporttype=='preview_ca') {
            //collab act preview
            $convertor=new refxml();
            $options=new stdClass();
            $options->unittest=true;
            $output = $convertor->transform_out($data,'MoodleFilter',$options);
            echo format_text($output);

        } else {
            //SC and resource page preview

            //convert refstyle from refworks mod quikbib str into apibib string text
            $returnedstyle = required_param('refstyleselect',PARAM_TEXT);
            $referencestyles = apibib::get_referencestyles();
            $refstyle=$referencestyles[0]['string'];
            foreach ($referencestyles as $style) {
                if ($style['string'] == $returnedstyle) {
                    $refstyle = $style['string'];
                }
            }

            //get reference titles from quikbib
            $titles=apibib::getbib($data,$refstyle,'RefWorks XML');
            if (!is_array($titles)) {
                $titles=array();
            }

            //get all the links
            $linking = linking::create_link($refxml);

            $refs = $refxml->getElementsByTagName('reference');
            for ($a=0,$max=$refs->length;$a<$max;$a++) {

                $title='';
                if (array_key_exists($a,$titles)) {
                    $title=$titles[$a];
                }
                //description
                $desc = '';
                $notes = $refs->item($a)->getElementsByTagName('no');
                if ($notes->length > 0) {
                    $noteval = $notes->item(0)->nodeValue;
                    $desc = clean_param($noteval, PARAM_TEXT);
                }
                //reference id
                $refid = $refs->item($a)->getElementsByTagName('id');
                if ($refid->length > 0) {
                    $refidval = $refid->item(0)->nodeValue;
                    $id = clean_param($refidval, PARAM_INT);
                }
                $weblink = $linking[$a];

                //create "reference" item
                self::add_ref_item($title,$weblink,$desc,$id,$reporttype);
            }
        }
        echo '</div>';
    }

    /**
     * Writes out each reference
     * @param $title string - reference title
     * @param $data string - the xml data to be saved
     * @param $weblink string - optional url
     * @param $desc string - description
     * @param $id int - reference id
     * @return unknown_type
     */
    private function add_ref_item($title,$weblink=null,$desc,$id,$type) {

        global $CFG;

        $alttitle=strip_tags(htmlspecialchars($title));

        //start item layout
        if ($type=='preview_rp') {
            $output = '<div class="report_resourcepagetype_column"><div>';
        }else if ($type=='preview_sc') {
            $output = '<div class="report_sctype_column"><div class="report_sctype_subcolumn">';
        }
        //edit ref form
        $output.= '<form id="ref_updates_'.$id.'" method="post" action="../managerefs.php"><input type="image" name="update" src="'.refworks_display::get_image_paths()->edit.'" alt="'.get_string('ref_update','refworks').'" title="'.get_string('ref_update','refworks').' '.$alttitle.'" /> ';

        $output.= '<input type="hidden" name="refid" value="'.$id.'" />';
        $output.= '<input name="sesskey" type="hidden" value="'.sesskey().'" />';
        if (refworks_base::$isinstance) {
            //if in instance also need id to be sent
            $output.= '<input type="hidden" name="id" value="'.refworks_base::$cm->id.'" />';
        }
        //create "reference" item
        if ($type=='preview_rp') {

            if ($weblink) {
                $output.= '<a href="'.$weblink.'">'.$title.'</a></form></div>';
            } else {
                $output.= $title.'</form></div>';
            }
            $output.='<div class="report_resourcepagetype_desc">'.$desc.'</div>';
            $output.='</div>';

        }else if ($type=='preview_sc') {

            $output.= $title;
            if ($weblink!='') {
                $output .= ' <a href="'.$weblink.'"><img src="'.refworks_display::get_image_paths()->source.'" alt="'.get_string('ref_sourcelink','refworks').' '.$alttitle.'" title="'.get_string('ref_sourcelink','refworks').' '.$alttitle.'" class="sc_image" /></a>';
            }
            $output.='</form></div></div>';
        }
        echo $output;
    }
}

?>