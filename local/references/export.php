<?php
/**
 * Helper class to control all of the exporting of references to various sources.
 * Do not directly call instance - extend your class and call $this->initexport()
 * This class (unlike others in this package) contains moodle code
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package references
 */
global $CFG;
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/accesslib.php');
require_once(dirname(__FILE__).'/convert/refxml.php');

class references_export extends refxml{

    const DOWNLOAD='download';
    const EXPORT='export';

    public $imageloc='';

    //call this function to initialise the export library
    public function initexport() {
        parent::__construct();
        global $CFG;
        $this->imageloc=$CFG->wwwroot.'/local/references/images/';
        //add descriptions to any export types that you wish to support here
        $this->add_export_description('RIS',get_string('refexport_ris','local_references'),get_string('refexport_risdesc','local_references'),self::DOWNLOAD,'download_32');
        //$this->add_export_description('MyStuff',get_string('refexport_mystuff','local_references'),get_string('refexport_mystuffdesc','local_references'),self::EXPORT,'my_stuff_32');
        $this->add_export_description('RefWorksDE',get_string('refexport_rwde','local_references'),get_string('refexport_rwdedesc','local_references'),self::EXPORT,'export_32');
        $this->add_export_description('MoodleFilter',get_string('refexport_moodlefilter','local_references'),get_string('refexport_moodlefilterdesc','local_references'),self::EXPORT,'export_32');
        //$this->add_export_description('BIBO_RDFa',get_string('refexport_bibordfa','local_references'),get_string('refexport_bibordfadesc','local_references'),self::EXPORT,'export_32');
        $this->add_export_description('RefWorksXML',get_string('refexport_rwxml','local_references'),get_string('refexport_rwxmldesc','local_references'),self::DOWNLOAD,'download_32');
    }

    //returns any content that this library needs in the HEAD tag
    //e.g. js, css, meta as HTML string
    protected function return_headerlibs() {
        return '';//DEPRECATED IN MOODLE2 (can't write headers anymore)
    }

    /**
     * Works out whether returning to the form after submitting
     * @return string: format name or null if not submitted
     */
    protected function return_submitted() {
        //loop thru all the available formats and check if a post
        for ($a=0,$max=count($this->formats);$a<$max;$a++) {
            if ($this->get_format_can_export($this->formats[$a])) {
                //see if submit of image button (sends buttonmane_x in post)
                $didsubmit=optional_param('submit_'.$this->formats[$a]['name'].'_x',NULL,PARAM_INT);
                if ($didsubmit!==NULL) {
                    return $this->formats[$a]['name'];
                }
            }
        }
        return NULL;
    }

    /**
     * Builds up an html string with the form for displaying export options
     * options.post=location to post to
     * options.hidden=array of hidden inputs
     * options.hiddenvals=array of any values for the hidden fields
     * options.ignore=array of formats to ignore and not display
     * @param $options object
     * @return string
     */
    protected function return_export_menu(&$options) {
        $post='';
        if (isset($options->post)) {
            $post=$options->post;
        }
        $ignore=array();
        if (isset($options->ignore)) {
            $ignore=$options->ignore;
        }
        $referurl='';
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referurl = urlencode($_SERVER['HTTP_REFERER']);
        }
        $form='<form action="'.$post.'" method="post" accept-charset="utf-8" id="mform1" class="mform refexportmenu" enctype="multipart/form-data">';
        $form.='<div style="display: none;">';
        $form.='<input name="sesskey" type="hidden" value="'.sesskey().'" />';
        $form.='<input name="referer" type="hidden" value="'.$referurl.'" />';
        //setup hidden fields
        if (isset($options->hidden) && is_array($options->hidden)) {
            for ($a=0,$max=count($options->hidden);$a<$max;$a++) {
                $form.='<input name="'.$options->hidden[$a].'" type="hidden" ';
                if (isset($options->hiddenvals) && is_array($options->hiddenvals) && isset($options->hiddenvals[$a])) {
                    $form.='value="'.$options->hiddenvals[$a].'"';
                }
                $form.='/>';
            }
        }
        $form.='</div>';
        //$form.='<div class="ou-corner-tr"></div><div class="ou-corner-bl"></div><div class="ou-corner-br"></div>';

        //create all exports first
        $form.=$this->generate_export_category(self::EXPORT,get_string('refexport_exportto','local_references'),$ignore);
        //downloads
        $form.=$this->generate_export_category(self::DOWNLOAD,get_string('refexport_downloadto','local_references'),$ignore);

        $form.='</form>';
        //add in some javascript to make the description text clickable
        //TODO Can include inline js in Moodle2? If not make function in js file called by $PAGE->requires->js_init_call()
        $form.=<<<SCRIPT
        <script type="text/javascript" language="javascript">
        try{
            var opts=document.getElementsByTagName("div");
            var optlen=opts.length;
            for (var a=0;a<optlen;a++) {
                if (opts[a].className=="exportDetails") {
                    var textnode=opts[a].childNodes[0];
                    textnode.style.cursor="pointer";
                    textnode.onclick=refx_dclick;
                }
            }

        }catch(e) {};
        function refx_dclick() {
            try{
                this.parentNode.parentNode.childNodes[0].click();
            }catch(e) {};
        }
        </script><noscript></noscript>
SCRIPT;
        $form=preg_replace('/\s\s+/', '', trim($form));
        return $form;
    }

    private function generate_export_category($type,$header,$ignore) {
        $matched=false;
        $form='<fieldset class="clearfix"  id="export_'.$type.'">'
        .'<legend class="ftoggler">'.$header.'...</legend>'
        .'<div class="clearfix"></div>';
        foreach ($this->formats as $format) {
            if (!in_array($format['name'],$ignore)) {
                if (isset($format['type']) && $format['type']==$type) {
                    if ($this->get_format_can_export($format)) {
                        $form.=$this->generate_export_option($format);
                        $matched=true;
                    }
                }
            }
        }
        $form.='</fieldset>';
        if ($matched) {
            return $form;
        } else {
            return '';
        }
    }

    private function generate_export_option(&$format) {
        global $OUTPUT;
        //get image path
        $img = $OUTPUT->pix_url($format['image'], 'local_references');

        $option='<div class="fitem"><input type="image"';
        $option.=' src="'.$img.'"';
        $option.=' name="submit_'.$format['name'].'"';
        $option.=' alt="';
        if ($format['type']==self::DOWNLOAD) {
            $alt=get_string('refexport_downloadto','local_references').' '.$format['disname'];
        }
        if ($format['type']==self::EXPORT) {
            $alt=get_string('refexport_exportto','local_references').' '.$format['disname'];
        }

        $option.=$alt.'" title="'.$alt.'"/>';

        $option.='<div class="exportDetails"><div>'.$format['disname'].'</div>';
        $option.=$format['description'].'</div></div>';
        return $option;
    }

    /**
     * adds export info to fomats array in refxml class
     * @param $name string: match name field in formats array
     * @param $friendlyname string: name that will be displayed
     * @param $desc string: description that will be displayed
     * @param $type string: either export or download
     * @return success
     */
    private function add_export_description($name,$friendlyname,$desc,$type,$image) {
        for ($a=0,$max=count($this->formats);$a<$max;$a++) {
            $curformat=&$this->formats[$a];
            if ($curformat['name']==$name) {
                $curformat['disname']=$friendlyname;
                $curformat['description']=$desc;
                $curformat['type']=$type;
                $curformat['image']=$image;
                return true;
            }
        }
        return false;
    }

    // returns result of the format sent class method can_export
    protected function get_format_can_export(&$format) {
        //test special moodle cases first (so no need for moodle code in conversion libs)
        if ($format['name']=='MyStuff') {
            if (file_exists(dirname(__FILE__).'/../../mod/portfolio/index.php')) {
                global $COURSE;
                $modcontext = context_course::instance($COURSE->id);
                if (has_capability('mod/portfolio:doanything', $modcontext, NULL, true, 'portfolio:doanything:false', 'portfolio')) {
                    return true;
                }
            }
            return false;
        }
        //capability check for some export types
        $iscapcheck='';
        if ($format['name']=='RefWorksXML') {
            $iscapcheck='mod/refworks:export_rwxml';
        }else if ($format['name']=='MoodleFilter') {
            $iscapcheck='mod/refworks:export_collab';
        }
        if ($iscapcheck!='') {
            if (file_exists(dirname(__FILE__).'/../../mod/refworks/refworks_base.php')) {
                @include_once(dirname(__FILE__).'/../../mod/refworks/refworks_base.php');
                global $COURSE;
                if (refworks_base::check_capabilities($iscapcheck)) {
                    return true;
                } else {
                    return false;
                }
            }
        }
        //return can export result from format
        return call_user_func(array($format['class'],'can_export'));
    }

    //EXPORT/DOWNLOAD METHODS

    /**
     * Starts the apporiate action for the type of 'export':
     * calls function in format.php abstract class (or child)
     * @param $name string: name matching format type
     * @param $data mixed:correct data the type of 'export' expects
     * @param $options mixed:any options it expects
     * @return success
     */
    protected function start_export_action($name,$data,$options=NULL) {
        //first find the format we are dealing with ($name)
        if ($this->return_format_export_type($name)==self::DOWNLOAD) {
            //download a file, expecting an object back with contents,file and [mime]
            $result=$this->transform_out($data,$name,$options);
            if ($result!==false && isset($result->contents)) {
                if (isset($result->mime)) {
                    self::saveFile($result->contents,$result->file,$result->mime);
                } else {
                    self::saveFile($result->contents,$result->file);
                }
                return true;//assume success
            }
            return false;
        }else if ($this->return_format_export_type($name)==self::EXPORT) {
            //do an export to another site/service etc
            $result=$this->transform_out($data,$name,$options);
            return $result;
        }
        return false;
    }

    //returns the type of the named format
    protected function return_format_export_type($name) {
        for ($a=0,$max=count($this->formats);$a<$max;$a++) {
            if ($this->formats[$a]['name']==$name && isset($this->formats[$a]['type'])) {
                return $this->formats[$a]['type'];
            }
        }
        return NULL;
    }

    //calls moodle
    public static function saveFile($path,$filename,$mime='text/plain') {
        send_file($path,$filename,'default',0,true,true,$mime);
    }
}

?>