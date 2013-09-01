/**
 * @copyright &copy; 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */

//onchange submit of reference sorting
function sorting_change(){
	try{
		document.getElementById("sorting").submit();
	}
	catch(e){
		try{
			document.getElementById("ref_sel_form").submit();
		}
		catch(e){

		}
	}
}
//create toggle link for create reference page (used for alternating display between all and recommended fields)
var divtopopulate = document.getElementById("createref_container_toggle");
if(divtopopulate){
	var createrefstateindicator = "recommended";
	divtopopulate.innerHTML = '<div class="fitem"><div class="fitemtitle"></div><div class="felement ftext"><a href="#" id="create_ref_toggle" onclick="update_create_ref_display();">Display all fields</a></div></div>';
}
//function to update the createref form
function update_create_ref_display(){
	if(createrefstateindicator == "recommended"){
		divtopopulate.innerHTML = '<div class="fitem"><div class="fitemtitle"></div><div class="felement ftext"><a href="#" id="create_ref_toggle" onclick="update_create_ref_display();">Display only recommended fields</a></div></div>';
		createrefstateindicator = "all";
	}else{
		divtopopulate.innerHTML = '<div class="fitem"><div class="fitemtitle"></div><div class="felement ftext"><a href="#" id="create_ref_toggle" onclick="update_create_ref_display();">Display all fields</a></div></div>';
		createrefstateindicator = "recommended";
	}
	update_ref_form_fields(document.getElementById('id_rt'), document.getElementById("mform1"), true);
}
//Code for reference selection pages
var selform='';
var selform_refs=null;
var selform_flds=null;

var cancelurl="";//used on cancel buttons
try{
	selform = document.getElementById('ref_sel_form');
	if(selform){
		sel_pagination_fix();
	}

	//js shortcuts - stops need to reload some pages on cancel actions
	var cancely = document.getElementById('id_cancel');
	if(cancely){
		//try and find a hidden field called refer
		var mform = document.getElementById('mform1');
		if(mform){
			var inputs = mform.getElementsByTagName('input');
			var max2 = inputs.length;
			for(var b=0;b<max2;b++){
				if(inputs[b].type=='hidden' && inputs[b].name=='refer'){
					cancelurl = inputs[b].value;
					break;
				}
			}
		}
		if(cancelurl==""){
			//use history as refer not found
			cancely.onclick=function(){history.back(1);return false;};
		}else{
			cancely.onclick=function(){document.location.href=cancelurl;return false;};
		}

	}else{
		//search for other form with a cancel (e.g. yes/no options)
		//These should be forms with hidden val cancelurl
		var allforms = document.getElementsByTagName('form');
		var max = allforms.length;
		for(var a=0;a<max;a++){
			if(cancelurl!=""){
				break;
			}
			var inputs = allforms[a].getElementsByTagName('input');
			var max2 = inputs.length;
			for(var b=0;b<max2;b++){
				if(inputs[b].type=='hidden' && inputs[b].name=='cancelurl'){
					cancelurl = inputs[b].value;
				}
				if(inputs[b].type=='submit' && cancelurl!="" && inputs[b].id!="invite"){
					inputs[b].onclick=function(){document.location.href=cancelurl;return false;};
					break;
				}
				//Invite forms
				if(inputs[b].type=='submit' && inputs[b].name=="cancel"){
					inputs[b].onclick=function(){history.back(1);return false;};
				}
			}
		}
	}

	//Rubbish focus change to main content...
	var maincontent = document.getElementById("notice");
	if (maincontent) {
		var inputs = maincontent.getElementsByTagName("input");
		var maxins = inputs.length;
		for (var buts=0; buts < maxins; buts++) {
			if (inputs[buts].type == "submit") {
				inputs[buts].focus();
				break;
			}
		}
	}

	//Code to change reference form labels depending on ref type
	var mform = document.getElementById('mform1');
	if(mform){
		var rt = document.getElementById('id_rt');
		if(rt){
			update_ref_form_fields(rt, mform);
			rt.focus();
		}else{
			//set the first form element to have focus
			var inputs = mform.getElementsByTagName('input');
			for(var c=0;c<inputs.length;c++){
				if(inputs[c].type!="hidden"){
					inputs[c].focus();
					break;
				}
			}
		}
	}

}catch(e){};

try{
	var dordiv = document.getElementById('dormant_accounts');
	var dormant_toggle_label = document.getElementById('dormant_toggle_label');
	if(dordiv.style.display!='block'){
		dormant_toggle_label.innerHTML = "Show Dormant Accounts";
		dordiv.style.display='none';
	}
}catch(e){
}

//check for doi field value if 'Reference retrieval from DOI' is being used
//check for isbn field value if 'Reference retrieval from ISBN' is being used
try{
	//create createref.php and managerefs.php specific variables (contained in 'referencedetail' div)
	//general variables used within functions
	var submitbutton = document.getElementById("id_submitbutton");
	var doibutton = document.getElementById("id_get_data");
	var snbutton = document.getElementById("id_get_data_isbn");

	var doifield = document.getElementById('id_do');
	var snfield = document.getElementById('id_sn');
	var reffrom = document.getElementById("mform1");
	var felements = reffrom.elements;

	if(doibutton){//Get data (DOI) button
		doibutton.onclick=function(evt){
		if(typeof(event)!="undefined"){
			evt = event;
		}
		//This button acts as default submit, need to determin actual selection
			if(typeof(evt.offsetX)!="undefined"){
				if(evt.offsetX > 0 && evt.offsetX>this.offsetWidth){
					return false;
				}else{
					//check against chrome keyboard press as this will always be 0
					if(evt.offsetX == 0 && evt.screenX == 0){
						return false;
					}
				}
			}else if(typeof(evt.explicitOriginalTarget)!="undefined"){
				if(evt.explicitOriginalTarget.id!="id_get_data"){
					return false;
				}
			}
			if(doifield.value=='' || doifield.value==null){
				alert("Please enter a value in the DOI field to use the 'Get data' service");
				doifield.focus();
				return false;
			}
			update_ref_form_fields(rt, document.getElementById("mform1"), true);
			document.getElementById('id_sn').value=""; //added for managerefs.php
			var farray2 = new Array();
			farray2 = document.getElementsByName("hiddendoi");
			farray2[0].value = doifield.value;
			return true;
		};
	}

	var tempval ="";
	if(submitbutton){//Create reference button
		submitbutton.onclick=function(){
			if(snfield){
				var f = document.getElementsByName("hiddensn");
				f[0].value = snfield.value;
				snfield.value="";
			}
			if(doifield){
				var f = document.getElementsByName("hiddendoi");
				f[0].value = doifield.value;
				doifield.value="";
			}
		};
	}

	if(snbutton){//Get data (ISBN) button
		snbutton.onclick=function(){
			if(snfield.value=='' || snfield.value==null){
				alert("Please enter a value in the ISBN field to use the 'Get data' service");
				snfield.focus();
				return false;
			}
			update_ref_form_fields(rt, document.getElementById("mform1"), true);

			document.getElementById('id_do').value=""; //added for managerefs.php
			tempval = snfield.value;
			tempval = tempval.replace(/-/gi,"");
			tempval = tempval.replace(/\s+/gi,"");
			isbnlength = tempval.length;
			var patt = /\D/; //a  test for not numeric
			var pattx = /[^0-9xX]/;//a test for not nueric and not x
			switch(isbnlength){
				case 10:
					var firstnine = tempval.substring(0,9);
					var lastchar = tempval.substring(9);
					if(patt.test(firstnine)== true || pattx.test(lastchar)== true){
						alert("The first 9 characters of a 10 character ISBN must be digits, the last character must be a digit or an 'x'");
						return false;
					}
					break;
				case 13:
					if(patt.test(tempval)== true){
						alert("13 character ISBNs cannot include Alphabetic characters");
						return false;
					}
					break;
				default:
					alert("Only ISBNs can be retrieved, please ensure you have entered a valid ISBN.\rISBNs should be 10 or 13 digits (although the last character in a 10 digit ISBN could be an'X')");
					return false;
			}
			var farray = new Array();
			farray = document.getElementsByName("hiddensn");
			farray[0].value = tempval;
			return true;
		};
	}
}catch(e){
}
//function to check for getdata activity (doi/isbn)
function check_if_getdata(){
	var hi = document.getElementsByName("hiddensn");
	var hd = document.getElementsByName("hiddendoi");
	if((hi[0].value!= "") || (hd[0].value!= "")){
		return true;
	}
	return false;
}
//Updates the form labels with values relating to the reference type
function update_ref_form_fields(select, mform, update){
	//check first if we have are refreshing page only because we are using getdata from and doi or an isbn and if we are then return(exit).
	var checkgetdata = check_if_getdata();
	if(checkgetdata==true){
		if(typeof(createrefstateindicator)!='undefined' && createrefstateindicator=='all'){
				update_create_ref_display()
		}
		//clear hidden trigger fields
		var hi = document.getElementsByName("hiddensn");
		var hd = document.getElementsByName("hiddendoi");
		hi[0].value = "";
		hd[0].value = "";
	}

	if(typeof(update)=="undefined"){
		//update used on select change, else no need as page load
		update = false;
	}

	//set onchange handler
	select.onchange = update_ref_form_handle;

	var tags = new Array("sp","vo","is","ed","ul","t1","t2","no","fd","jf","pb","a1");//this should match all tags that have alternate labels

	var original = new Array();//array of original (default) text labels (must match tags)
	original["sp"] = "Start page";
	original["vo"] = "Volume";
	original["is"] = "Issue";
	original["ed"] = "Edition (number only)";
	original["ul"] = "Web address (URL)";
	original["t1"] = "Title";
	original["t2"] = "Secondary title";
	original["no"] = "Notes"
	original["fd"] = "Publication day/month";
	original["jf"] = "Journal title";
	original["pb"] = "Publisher";
	original["a1"] = "Authors (<em>separate with ;</em>)";

	var alts = new Array(
			//array of all the alt values. Object ["type"] should match the text value of the type of reference
			{"name":"Book, Edited","a1":"Editors (<em>separate with ;</em>)","sp":"Total pages"},
			{"name":"Book, Section","t2":"Book title","t1":"Section title"},
			{"name":"Journal, Electronic", "t1":"Article title", "fd":"Date Accessed"},
			{"name":"Magazine Article", "t1":"Article title", "jf":"Magazine title"},
			{"name":"Newspaper Article", "t1":"Article title", "jf":"Newspaper title"},
			{"name":"Web Page","sp":"Total pages","vo":"Accessed year","is":"Accessed day/month","ed":"Webpage URL"},
			{"name":"Bills/Resolutions","sp":"Start section","vo":"Number","is":"Session"},
			{"name":"Dissertation/Thesis","ed":"Degree Type","pb":"Institution"},
			{"name":"Video/DVD","a1":"Director","pb":"Distributor or Studio"},
			{"name":"Online Discussion Forum/Blogs","fd":"Publication day/month/year","jf":"Blog title"},
			{"name":"Conference Proceedings","t2":"Conference Title"}	);
	
	var masterarray = [];
	var searcharray = ['container_searchdoi','container_searchissn','container_searchisbn','container_searchprimorid'];
	// In following section, push a reference type to masterarray, followed by pushing the related list of 'recommended' fields
	// to reccomendedarray (sic)
	var reccomendedarray = new Array(); //array of div containers relating to masterarray
	//book, whole
	masterarray.push('Book, Whole');
	reccomendedarray[0]=['createref_container_toggle','container_authors','container_title','container_year','container_edition',
							'container_publisher','container_placepub','container_sn',
							'container_av','container_lk','container_db','container_retrieved',
							'container_u5', 'container_no'];
	reccomendedarray[0] = reccomendedarray[0].concat(searcharray);
	//book, section
	masterarray.push('Book, Section');
	reccomendedarray[1]=['createref_container_toggle','container_authors','container_title','container_editor','container_title2',
							'container_year','container_edition','container_publisher','container_placepub','container_sn',
							'container_page','container_otherpage',
							'container_av','container_lk','container_db','container_retrieved',
							'container_u5','container_no'];
	reccomendedarray[1] = reccomendedarray[1].concat(searcharray);
	//book edited
	masterarray.push('Book, Edited');
	reccomendedarray[2]=['createref_container_toggle','container_title','container_authors','container_year','container_edition',
							'container_publisher','container_placepub','container_sn',
							'container_av','container_lk','container_db','container_retrieved',
							'container_u5', 'container_no'];
	reccomendedarray[2] = reccomendedarray[2].concat(searcharray);
	//journal article
	masterarray.push('Journal Article');
	reccomendedarray[3]=['createref_container_toggle','container_authors','container_title','container_periodical','container_year',
							'container_volume','container_issue','container_page','container_otherpage','container_sn',
							'container_doi',
							'container_av','container_lk','container_db','container_retrieved',
							'container_u5','container_no'];
	reccomendedarray[3] = reccomendedarray[3].concat(searcharray);
	// web pages
	masterarray.push('Web Page');
	reccomendedarray[4]=['createref_container_toggle','container_authors','container_title','container_year','container_edition',
							'container_volume','container_issue',
							'container_u5','container_no'];
	reccomendedarray[4] = reccomendedarray[4].concat(searcharray);
	//newspaper article
	masterarray.push('Newspaper Article');
	reccomendedarray[5]=['createref_container_toggle','container_authors','container_title','container_periodical','container_year',
							'container_pub_date_free','container_issue','container_page','container_otherpage','container_doi',
							'container_av','container_lk','container_db','container_retrieved',
							'container_u5','container_no'];
	reccomendedarray[5] = reccomendedarray[5].concat(searcharray);
	//magazine article
	masterarray.push('Magazine Article');
	reccomendedarray[6]=['createref_container_toggle','container_authors','container_title','container_periodical','container_year',
							'container_pub_date_free','container_issue','container_page','container_otherpage','container_doi',
							'container_av','container_lk','container_db','container_retrieved',
							'container_u5','container_no'];
	reccomendedarray[6] = reccomendedarray[6].concat(searcharray);
	//Video/DVD
	masterarray.push('Video/DVD');
	reccomendedarray[7]=['createref_container_toggle','container_authors','container_title','container_year',
							'container_pub_date_free','container_publisher','container_placepub',
							'container_av','container_lk','container_db','container_retrieved',
							'container_u5', 'container_no'];
	reccomendedarray[7] = reccomendedarray[7].concat(searcharray);
	//Dissertation/Thesis
	masterarray.push('Dissertation/Thesis');
	reccomendedarray[8]=['createref_container_toggle','container_authors','container_title','container_year','container_edition',
							'container_publisher','container_placepub','container_sn',
							'container_av','container_lk','container_db','container_retrieved',
							'container_u5', 'container_no'];
	reccomendedarray[8] = reccomendedarray[8].concat(searcharray);
	//Conference Proceedings
	masterarray.push('Conference Proceedings');
	reccomendedarray[9]=['createref_container_toggle','container_authors','container_title','container_editor','container_title2',
							'container_year','container_publisher','container_placepub','container_doi','container_sn',
							'container_page','container_otherpage',
							'container_av','container_lk','container_db','container_retrieved',
							'container_u5','container_no'];
	reccomendedarray[9] = reccomendedarray[9].concat(searcharray);
	//Online Discussion Forum/Blogs
	masterarray.push('Online Discussion Forum/Blogs');
	reccomendedarray[10]=['createref_container_toggle','container_authors','container_title','container_periodical',
							'container_year','container_pub_date_free',
							'container_av','container_lk','container_retrieved',
							'container_u5', 'container_no'];
	reccomendedarray[10] = reccomendedarray[10].concat(searcharray);
	//Artwork
	masterarray.push('Artwork');
	reccomendedarray[11]=['createref_container_toggle','container_authors','container_title','container_year','container_k1',
							'container_av','container_lk','container_db','container_retrieved',
							'container_u5', 'container_no'];
	reccomendedarray[11] = reccomendedarray[11].concat(searcharray);
	//generic
	masterarray.push('Generic');
	reccomendedarray[12]=['createref_container_toggle','container_authors','container_title','container_year','container_publisher',
							'container_placepub','container_page','container_otherpage','container_edition','container_sn',
							'container_av','container_lk','container_db','container_retrieved',
							'container_u5','container_no']; 
	reccomendedarray[12] = reccomendedarray[12].concat(searcharray);
	//End of creation of recommended fields lists

	//create the containers array from the current document
	var alldivs = document.getElementsByTagName('div');
	var containers = new Array();
	containers = [];
	for(var xx=0;xx<alldivs.length;xx++){
		if(alldivs[xx].className == "mrefformcontainer" && alldivs[xx].id!="createref_container_toggle"){
			containers.push(alldivs[xx].id);
		}
	}

	var selected = select.selectedIndex;

	//get the name of the current type
	var options = select.getElementsByTagName('option');
	var curname = options[selected].innerHTML;
	//go through and update 'recommended fields' if required by user
	var arrayasstring="";
	var masterarrayasstring = masterarray.toString();
	var regulatedfields = masterarrayasstring.indexOf(curname);
	if(regulatedfields != -1){
		//show link
		if(divtopopulate){
			divtopopulate.style.display = "block";
		}
	}else{
		//hide link as no recommended fields
		if(divtopopulate){
			divtopopulate.style.display = "none";
		}
	}
	if(createrefstateindicator == "recommended" && regulatedfields != "-1"){
		for(var c=0;c<masterarray.length;c++){
			if(masterarray[c]==curname){
				for(var d=0;d<containers.length;d++){
					arrayasstring = reccomendedarray[c].toString();
					if((arrayasstring.indexOf(containers[d]))=="-1"){
						document.getElementById(containers[d]).style.display = "none";
					}else{
						document.getElementById(containers[d]).style.display = "block";
					}
				}
			}
		}
	}else{
		//display all fields
		for(var g=0;g<containers.length;g++){
			document.getElementById(containers[g]).style.display = "block";
		}
	}
	//go through all the alt types and see if match, then go through all tags and update
	for(var a=0;a<alts.length;a++){
		if(alts[a]["name"] == curname){
			//matched alt with cur type
			for(var b=0;b<tags.length;b++){
				if(typeof(alts[a][tags[b]])!="undefined"){
					update_ref_form_label(mform, tags[b],alts[a][tags[b]]);
				}else{
					//not defined in alt, use original value
					if(update){
						update_ref_form_label(mform, tags[b],original[tags[b]]);
					}
				}
			}
			return;
		}
	};
	if(update){
		//default use original vals if not a matching type
		for(var b=0;b<tags.length;b++){
			update_ref_form_label(mform, tags[b],original[tags[b]]);
		}
	}
}

try{
	var sharedacctable = document.getElementById("sharedaccountstable");
	if (sharedacctable) {
		var tds = sharedacctable.getElementsByTagName("td");
		for(var a=0,len=tds.length;a<len;a++) {
			if (tds[a].title) {
				tds[a].onclick = function(e) {
					var evt = window.event || e;
					var top = typeof(evt.screenY) != 'undefined' ? evt.screenY : evt.screenTop;
					var left = typeof(evt.screenX) != 'undefined' ? evt.screenX : evt.screenLeft;
					var newwin = window.open('', '_blank', 'width=250, height=100, left='+left+', top='+top);
					var contents = this.title.split("||").join("<br/>");
					newwin.document.body.innerHTML = contents;
				}
			}
		}
	}
} catch(e) {

}

//updates label, based on tag (form element name)
function update_ref_form_label(form, tag,value){
	var labels = form.getElementsByTagName("label");
	for(var a=0,len=labels.length;a<len;a++){
		if(labels[a].htmlFor == "id_"+tag){
			labels[a].innerHTML = value;
			return;
		}
	}
}
//onchange handler for ref type select
function update_ref_form_handle(){
	update_ref_form_fields(this, document.getElementById("mform1"), true);
}
function sel_pagination_fix(){
	try{
		//Make all input checkboxes that have name r_xx have onselect handler to add to selected input field
		//Make all input checkboxes that have name fl_xx have onselect handler to add to selectedfl input field
		var allinputs = document.getElementsByTagName('input');
		var max = allinputs.length;
		for(var a=0;a<max;a++){
			if(allinputs[a].type=='checkbox'){
				if(allinputs[a].name.indexOf('r_')==0){
					//reference checkbox
					allinputs[a].onclick = sel_ref;
				}else if(allinputs[a].name.indexOf('fl_')==0){
					//folder checkbox
					allinputs[a].onclick = sel_fld;
				}
			}else if(allinputs[a].type=='hidden'){
				if(allinputs[a].name=='selected'){
					selform_refs = allinputs[a];
				}else if(allinputs[a].name=='selectedfl'){
					selform_flds = allinputs[a];
				}
			}
		}
		//Make any page pagination links into submits - they need to write hiiden fields to form with their params set
		var pglistdiv = document.getElementById("pagelist");
		var links = pglistdiv.getElementsByTagName("a");
		max = links.length;
		for(a=0;a<max;a++){
			//add onclick to each link, making them write appropriate hiiden field to form then submit
			links[a].onclick = function(){
				var src = this.href;
				var params = src.split("&");
				var max2 = params.length;
				for(var b=1;b<max2;b++){
					var paramsplit = params[b].split("=");
					selform.innerHTML+='<input type="hidden" style="display:none" name="'+paramsplit[0]+'" value="'+paramsplit[1]+'"/>';
				}
				selform.submit();
				return false;
			};
		}
	}
	catch(e){};
}

function sel_ref(){
	update_field(this.name, selform_refs);
}
function sel_fld(){
	update_field(this.name, selform_flds,true);
}
//Update hidden field with id of checkbox
function update_field(name, field,folder){
	var curval = field.value;
	if(curval==""){
		var curvalarray = new Array();
	}else{
		if(!folder){
			var curvalarray = curval.split(",");
		}else{
			var curvalarray = curval.split("@@");//folder can't use ,
		}
	}

	var arpos = -1;
	var max = curvalarray.length;
	for(var a=0;a<max;a++){
		if(curvalarray[a]==name){
			arpos = a;
			break;
		}
	}
	if(arpos==-1){
		curvalarray.push(name);
	}else{
		curvalarray.splice(arpos,1);
	}
	if(!folder){
		field.value = curvalarray.join();
	}else{
		field.value = curvalarray.join("@@");//folder can't use ,
	}
}
function showDormantAccounts(){
	//onclick on/off toggle for displaying dormant accounts
	try{
		var dordiv = document.getElementById('dormant_accounts');
		var dormant_toggle_label = document.getElementById('dormant_toggle_label');
		if(dordiv.style.display != 'block'){
			dordiv.style.display = 'block';
			dormant_toggle_label.innerHTML = "Hide Dormant Accounts";
		}else{
			dormant_toggle_label.innerHTML = "Show Dormant Accounts";
			dordiv.style.display='none';
		}
	}
	catch(e){
	}
}
function autoNotes(n){
	var note;
	note = document.getElementById('id_no').value;
	if (note.length > 0){
			note += '. ';
	}
	note += n;
	document.getElementById('id_no').value = note;
}