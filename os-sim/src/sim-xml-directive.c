/*
License:

   Copyright (c) 2003-2006 ossim.net
   Copyright (c) 2007-2009 AlienVault
   All rights reserved.

   This package is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; version 2 dated June, 1991.
   You may not use, modify or distribute this program under any other version
   of the GNU General Public License.

   This package is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this package; if not, write to the Free Software
   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
   MA  02110-1301  USA


On Debian GNU/Linux systems, the complete text of the GNU General
Public License can be found in `/usr/share/common-licenses/GPL-2'.

Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
*/

#include <sim-util.h>
#include <sim-net.h>
#include <sim-xml-directive.h>

#include "sim-inet.h"
#include <config.h>
#include <string.h>
#include <errno.h>

struct _SimXmlDirectivePrivate {
  SimContainer  *container;

  GList    *directives;
  GList    *groups;
};

#define OBJECT_DIRECTIVES       "directives"
#define OBJECT_DIRECTIVE        "directive"
#define OBJECT_RULE             "rule"
#define OBJECT_RULES            "rules"
#define OBJECT_ACTION           "action"
#define OBJECT_ACTIONS          "actions"

#define PROPERTY_ID             "id"
#define PROPERTY_NAME           "name"
#define PROPERTY_STICKY         "sticky"
#define PROPERTY_STICKY_DIFFERENT	"sticky_different"
#define PROPERTY_NOT			"not"
#define PROPERTY_TYPE           "type"
#define PROPERTY_PRIORITY       "priority"
#define PROPERTY_RELIABILITY    "reliability"
#define PROPERTY_REL_ABS        "rel_abs"
#define PROPERTY_CONDITION      "condition"
#define PROPERTY_VALUE          "value"
#define PROPERTY_INTERVAL       "interval"
#define PROPERTY_ABSOLUTE       "absolute"
#define PROPERTY_TIME_OUT       "time_out"
#define PROPERTY_OCCURRENCE     "occurrence"
#define PROPERTY_SRC_IP         "from"
#define PROPERTY_DST_IP         "to"
#define PROPERTY_SRC_PORT       "port_from"
#define PROPERTY_DST_PORT       "port_to"
#define PROPERTY_PROTOCOL       "protocol"
#define PROPERTY_PLUGIN_ID			"plugin_id"
#define PROPERTY_PLUGIN_SID			"plugin_sid"
#define PROPERTY_SENSOR					"sensor"
#define PROPERTY_FILENAME				"filename"
#define PROPERTY_USERNAME				"username"	//the following variables won't be used by every sensor
#define PROPERTY_PASSWORD				"password"
#define PROPERTY_USERDATA1			"userdata1"
#define PROPERTY_USERDATA2			"userdata2"
#define PROPERTY_USERDATA3			"userdata3"
#define PROPERTY_USERDATA4			"userdata4"
#define PROPERTY_USERDATA5			"userdata5"
#define PROPERTY_USERDATA6			"userdata6"
#define PROPERTY_USERDATA7			"userdata7"
#define PROPERTY_USERDATA8			"userdata8"
#define PROPERTY_USERDATA9			"userdata9"

#define OBJECT_GROUPS			"groups"
#define OBJECT_GROUP			"group"
#define OBJECT_APPEND_DIRECTIVE		"append-directive"
#define PROPERTY_DIRECTIVE_ID		"directive_id"


static void sim_xml_directive_class_init (SimXmlDirectiveClass *klass);
static void sim_xml_directive_init       (SimXmlDirective *xmldirect, SimXmlDirectiveClass *klass);
static void sim_xml_directive_finalize   (GObject *object);
static gchar *sim_xml_directive_get_search_type (SimRule *rule, int inx,gchar *value);
/*
 *  For the search type
 */
static struct{
	gchar *token;
	guint type;
}text_field_search_type[]={
	{"EXACT:",SimMatchTextEqual},
	{"FIND:",SimMatchTextSubstr},
	{"REGEX:",SimMatchTextRegex},
	{"PREV:",SimMatchPrevious},
	{"ANY",SimMatchTextAny},
	{NULL,0}
};

/*
 * SimXmlDirective object signals
 */
enum {
  SIM_XML_DIRECTIVE_CHANGED,
  SIM_XML_DIRECTIVE_LAST_SIGNAL
};

static gint xmldirect_signals[SIM_XML_DIRECTIVE_LAST_SIGNAL] = { 0, };
static GObjectClass *parent_class = NULL;


gboolean 
sim_xml_directive_new_groups_from_node (SimXmlDirective	*xmldirect,
					xmlNodePtr	node);
/*
 * SimXmlDirective class interface
 */

static void
sim_xml_directive_class_init (SimXmlDirectiveClass * klass)
{
  GObjectClass *object_class = G_OBJECT_CLASS (klass);
  
  parent_class = g_type_class_peek_parent (klass);
  
  xmldirect_signals[SIM_XML_DIRECTIVE_CHANGED] =
    g_signal_new ("changed",
		  G_TYPE_FROM_CLASS (object_class),
		  G_SIGNAL_RUN_LAST,
		  G_STRUCT_OFFSET (SimXmlDirectiveClass, changed),
		  NULL, NULL,
		  g_cclosure_marshal_VOID__VOID,
		  G_TYPE_NONE, 0);
  
  object_class->finalize = sim_xml_directive_finalize;
  klass->changed = NULL;
}

static void
sim_xml_directive_init (SimXmlDirective *xmldirect, SimXmlDirectiveClass *klass)
{
  g_return_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect));
  
  /* allocate private structure */
  xmldirect->_priv = g_new0 (SimXmlDirectivePrivate, 1);
  xmldirect->_priv->directives = NULL;
  xmldirect->_priv->groups = NULL;

}

static void
sim_xml_directive_finalize (GObject *object)
{
  SimXmlDirective *xmldirect = (SimXmlDirective *) object;
  
  g_return_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect));
  
  /* chain to parent class */
  parent_class->finalize (object);
}

GType
sim_xml_directive_get_type (void)
{
  static GType type = 0;
  
  if (!type) {
    static const GTypeInfo info = {
      sizeof (SimXmlDirectiveClass),
      (GBaseInitFunc) NULL,
      (GBaseFinalizeFunc) NULL,
      (GClassInitFunc) sim_xml_directive_class_init,
      NULL,
      NULL,
      sizeof (SimXmlDirective),
      0,
      (GInstanceInitFunc) sim_xml_directive_init
    };
    type = g_type_register_static (G_TYPE_OBJECT,
				   "SimXmlDirective",
				   &info, 0);
  }
  return type;
}

/*
 * Used to get the variable type from properties in the directive
 */
SimRuleVarType
sim_xml_directive_get_rule_var_from_property (const gchar *var)
{

  if (!strcmp (var, PROPERTY_FILENAME))
    return SIM_RULE_VAR_FILENAME;
  else if (!strcmp (var, PROPERTY_USERNAME))
    return SIM_RULE_VAR_USERNAME;
  else if (!strcmp (var, PROPERTY_PASSWORD))
    return SIM_RULE_VAR_PASSWORD;
  else if (!strcmp (var, PROPERTY_USERDATA1))
    return SIM_RULE_VAR_USERDATA1;
  else if (!strcmp (var, PROPERTY_USERDATA2))
    return SIM_RULE_VAR_USERDATA2;
  else if (!strcmp (var, PROPERTY_USERDATA3))
    return SIM_RULE_VAR_USERDATA3;
  else if (!strcmp (var, PROPERTY_USERDATA4))
    return SIM_RULE_VAR_USERDATA4;
  else if (!strcmp (var, PROPERTY_USERDATA5))
    return SIM_RULE_VAR_USERDATA5;
  else if (!strcmp (var, PROPERTY_USERDATA6))
    return SIM_RULE_VAR_USERDATA6;
  else if (!strcmp (var, PROPERTY_USERDATA7))
    return SIM_RULE_VAR_USERDATA7;
  else if (!strcmp (var, PROPERTY_USERDATA8))
    return SIM_RULE_VAR_USERDATA8;
  else if (!strcmp (var, PROPERTY_USERDATA9))
    return SIM_RULE_VAR_USERDATA9;

  return SIM_RULE_VAR_NONE;
}



/*
 *
 *
 *
 *
 */
SimDirective*
find_directive (SimXmlDirective	*xmldirect,
		gint		id)
{
  GList *list;

  g_return_val_if_fail (xmldirect, NULL);
  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), NULL);
  
  if (!id)
    return NULL;

  list = xmldirect->_priv->directives;
  while (list)
    {
      SimDirective *directive = (SimDirective *) list->data;
      gint cmp = sim_directive_get_id (directive);

      if (cmp == id)
	return directive;

      list = list->next;
    }

  return NULL;
}

/**
 * sim_xml_directive_new
 *
 * Creates a new #SimXmlDirective object, which can be used to describe
 * a directive which will then be loaded by a provider to create its
 * defined structure
 */
SimXmlDirective *
sim_xml_directive_new (void)
{
  SimXmlDirective *xmldirect;
  
  xmldirect = g_object_new (SIM_TYPE_XML_DIRECTIVE, NULL);
  return xmldirect;
}

/**
 * sim_xml_directive_new_from_file
 */
SimXmlDirective*
sim_xml_directive_new_from_file (SimContainer *container,
																 const gchar *file)
{
  SimXmlDirective *xmldirect;	//here will be stored all the directives, and all the groups
  gchar *body;
  xmlDocPtr doc;
  xmlNodePtr root;
  xmlNodePtr node;
	xmlParserCtxtPtr ctx;
	xmlErrorPtr error;
  GList			*list;
  GList			*ids;
  
  g_return_if_fail (container != NULL);
  g_return_if_fail (SIM_IS_CONTAINER (container));
  g_return_val_if_fail (file != NULL, NULL);
  
	ctx = xmlNewParserCtxt();
	if (!ctx){
		g_log(G_LOG_DOMAIN,G_LOG_LEVEL_MESSAGE,"Could not create XML Parser for file %s",file);
		return NULL;
	}
	/* parse an validate de XML directives.xml file*/
	doc = xmlCtxtReadFile(ctx,file,NULL,XML_PARSE_DTDVALID|XML_PARSE_NOENT);
  
  if (!doc) {
		error = xmlGetLastError();
		g_log(G_LOG_DOMAIN,G_LOG_LEVEL_MESSAGE,"Could not parse file at %s. Error:%s", file,error->message);
    return NULL;
  }
	if (!ctx->valid){
		error = xmlGetLastError();
		g_log(G_LOG_DOMAIN,G_LOG_LEVEL_MESSAGE,"Validate error in file: %s. Error %s",file,error->message);
	}
	xmlFreeParserCtxt(ctx);

	
  
  xmldirect = g_object_new (SIM_TYPE_XML_DIRECTIVE, NULL);
  xmldirect->_priv->container = container;

  /* parse the file */
  root = xmlDocGetRootElement (doc);				//we need to know the first element in the tree
  if (strcmp ((gchar *) root->name, OBJECT_DIRECTIVES)) 
	{
		g_log(G_LOG_DOMAIN,G_LOG_LEVEL_MESSAGE,"Invalid XML directive file '%s'", file);
		xmlFreeDoc(doc);
    g_object_unref (G_OBJECT (xmldirect))	;
    return NULL;
  }

  node = root->xmlChildrenNode;
  while (node) //while 
	{
    if (!strcmp ((gchar *) node->name, OBJECT_DIRECTIVE))		//parse each one of the directives and store it in xmldirect	
      sim_xml_directive_new_directive_from_node (xmldirect, node);

    if (!strcmp ((gchar *) node->name, OBJECT_GROUPS))
      sim_xml_directive_new_groups_from_node (xmldirect, node); // the same with directive groups

    node = node->next;
  }

	//now we have all the directives, and all the groups. But is needed to tell to each directive if it's is inside
	//a group
	list = xmldirect->_priv->groups;
  while (list)
  {
    SimDirectiveGroup *group = (SimDirectiveGroup *) list->data;
    GList *ids = sim_directive_group_get_ids (group);

    while (ids)
		{
		  gint id = GPOINTER_TO_INT (ids->data);
			SimDirective *directive= find_directive (xmldirect, id);

		  if (directive)
		    sim_directive_append_group (directive, group);

			ids = ids->next;
		}

    list = list->next;
  }
	xmlFreeDoc(doc);
  return xmldirect;
}

/**
 *
 *
 *
 */
void
sim_xml_directive_set_container (SimXmlDirective * xmldirect,
				 SimContainer *container)
{
  g_return_if_fail (xmldirect != NULL);
  g_return_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect));
  g_return_if_fail (container != NULL);
  g_return_if_fail (SIM_IS_CONTAINER (container));

  xmldirect->_priv->container = container;
}



/**
 * sim_xml_directive_changed
 * @xmldirect: XML directive
 *
 * Emit the "changed" signal for the given XML directive
 */
void
sim_xml_directive_changed (SimXmlDirective * xmldirect)
{
  g_return_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect));
  g_signal_emit (G_OBJECT (xmldirect),
		 xmldirect_signals[SIM_XML_DIRECTIVE_CHANGED],
		 0);
}

/**
 * sim_xml_directive_reload
 * @xmldirect: XML directive.
 *
 * Reload the given XML directive from its original place, discarding
 * all changes that may have happened.
 */
void
sim_xml_directive_reload (SimXmlDirective *xmldirect)
{
  /* FIXME: implement */
}

/**
 * sim_xml_directive_save
 * @xmldirect: XML directive.
 * @file: FILE to save the XML directive to.
 *
 * Save the given XML directive to disk.
 */
gboolean
sim_xml_directive_save (SimXmlDirective *xmldirect, const gchar *file)
{
  gchar			*xml;
  gboolean	result;
  
  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), FALSE);
  
  xml = sim_xml_directive_to_string (xmldirect);
  if (xml) 
	{
    result = sim_file_save (file, xml, strlen ((char *) xml));
    g_free (xml);
  }
	else
    result = FALSE;

  return result;
}

/**
 * sim_xml_directive_to_string
 * @xmldirect: a #SimXmlDirective object.
 *
 * Get the given XML directive contents as a XML string.
 *
 * Returns: the XML string representing the structure and contents of the
 * given #SimXmlDirective object. The returned value must be freed when no
 * longer needed.
 */
gchar *
sim_xml_directive_to_string (SimXmlDirective *xmldirect)
{
  xmlDocPtr doc;
  xmlNodePtr root;
  xmlNodePtr tables_node = NULL;
  GList *list, *l;
  xmlChar *xml;
  gint size;
  gchar *retval;
  
  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), NULL);
  
  /* create the top node */
  doc = xmlNewDoc ((xmlChar *) "1.0");
  root = xmlNewDocNode (doc, NULL, (xmlChar *) OBJECT_DIRECTIVES, NULL);
  xmlDocSetRootElement (doc, root);
  
  /* save to memory */
  xmlDocDumpMemory (doc, &xml, &size);
  xmlFreeDoc (doc);
  if (!xml) {
    g_message ("Could not dump XML file to memory");
    return NULL;
  }
  
  retval = g_strdup ((gchar *) xml);
  free (xml);
  
  return retval;
}

/*
 *
 * Parameter node is the same that a single directive inside the directives.xml. Its needed to extract
 * all the data from node and insert it into a SimDirective object to be
 * able to return it.
 *
 * http://xmlsoft.org/html/libxml-tree.html#xmlNode
 *
 * Returns NULL on error and don't load the directive at all.
 */
SimDirective*
sim_xml_directive_new_directive_from_node (SimXmlDirective  *xmldirect,
																				   xmlNodePtr        node)
{
  SimDirective  *directive;
  SimAction     *action;
  GNode         *rule_root;
  xmlNodePtr     children;
  xmlNodePtr     actions;
  gchar         *name;
  gchar         *value = NULL;
  gint           priority;
  gint           id;

  g_return_val_if_fail (xmldirect != NULL, NULL);
  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), NULL);
  g_return_val_if_fail (node != NULL, NULL);

  if (strcmp ((gchar *) node->name, (gchar *) OBJECT_DIRECTIVE))
    {
      g_message ("Invalid directive node %s", node->name);
      return NULL;
    }

  id = atoi( (char * ) (xmlGetProp (node, (xmlChar *) PROPERTY_ID))); //get the id of that directive 
  name = g_strdup_printf ("directive_event: %s", (char *) xmlGetProp (node, (xmlChar *) PROPERTY_NAME));
  
	g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "Loading directive: %d", id);

  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_PRIORITY)))
  {
    priority= strtol(value, (char **) NULL, 10);
    xmlFree(value);
  } 

  directive = sim_directive_new ();
  sim_directive_set_id (directive, id);
  sim_directive_set_name (directive, name);
  sim_directive_set_priority (directive, priority);

  children = node->xmlChildrenNode; // xmlChildrenNode is a #define to children. It's the same to do node->children
  while (children)   
	{
    if (!strcmp ((gchar *) children->name, OBJECT_RULE))
    {
			rule_root = sim_xml_directive_new_rule_from_node (xmldirect, children, NULL, 1);//pass all the directive to the
																																											//function and (separate && store) it
																																											//into individual rules
			if (!rule_root)
			{
				g_message ("Error: There is a problem in directive: %d. Aborting loading of that directive", id);
				return NULL;
			}
    }
    children = children->next;
  }

  /* The time out of the first rule is set to directive time out 
   * if the rule have occurence > 1, otherwise is set to 0.
   */
  if (rule_root)
  {
    SimRule *rule = (SimRule *) rule_root->data;
    gint time_out = sim_rule_get_time_out (rule);
    gint occurrence = sim_rule_get_occurrence (rule);
    if (occurrence > 1)
			sim_directive_set_time_out (directive, time_out);
    else
			sim_directive_set_time_out (directive, 0);
  }
  sim_directive_set_root_node (directive, rule_root);
  
  xmldirect->_priv->directives = g_list_append (xmldirect->_priv->directives, directive);

  return directive;
}


/*
 *
 *
 *
 *
 */
SimAction*
sim_xml_directive_new_action_from_node (SimXmlDirective *xmldirect,
					xmlNodePtr       node)
{
  SimAction  *action;

  g_return_if_fail (xmldirect != NULL);
  g_return_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect));
  g_return_if_fail (node != NULL);
  
  if (strcmp ((gchar *) node->name, (gchar *)  OBJECT_ACTION))
    {
      g_message ("Invalid action node %s", node->name);
      return NULL;
    }

  action = sim_action_new ();

  return action;
}
static gboolean sim_xml_directive_set_rule_exact (SimRule *rule,
																									gchar *text,
																									gchar *field_type){
	int inx;
	gchar *p;
	int res = FALSE;
	g_return_val_if_fail (rule != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (text != NULL, FALSE);
	g_return_val_if_fail (field_type != NULL, FALSE);
	g_return_val_if_fail ((inx = sim_text_field_get_index (field_type)) != -1, FALSE);
	p = text;
	if (text[0] == '!' && text[1]!='\0'){
		res = sim_rule_set_match_text (rule, inx, &text[1], TRUE);
		
	}else{
		res = sim_rule_set_match_text (rule,  inx, text, FALSE);
	}
	return res;
}
static gboolean sim_xml_directive_set_rule_substr (SimRule *rule,
																									gchar *text,
																									gchar *field_type){
	int inx;
	gchar *p;
	int res = FALSE;
	g_return_val_if_fail (rule != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (text != NULL, FALSE);
	g_return_val_if_fail (field_type != NULL, FALSE);
	g_return_val_if_fail ((inx = sim_text_field_get_index (field_type)) != -1, FALSE);
	p = text;
	if (text[0] == '!' && text[1]!='\0'){
		res = sim_rule_set_match_substr (rule, inx, &text[1], TRUE);
		
	}else{
		res = sim_rule_set_match_substr (rule,  inx, text, FALSE);
	}
	return res;
}

static gboolean sim_xml_directive_set_rule_regex (SimRule *rule,
																									gchar *text,
																									gchar *field_type){
	int inx;
	gchar *p;
	int res = FALSE;
	g_return_val_if_fail (rule != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (text != NULL, FALSE);
	g_return_val_if_fail (field_type != NULL, FALSE);
	g_return_val_if_fail ((inx = sim_text_field_get_index (field_type)) != -1, FALSE);
	return sim_rule_set_match_regex (rule, inx, text);
}
static gboolean sim_xml_directive_set_rule_var_match (SimRule *rule,
																									gchar *text,
																									gchar *field_type, int level){

	int inx;
	int var_inx;
	gchar *p;
	int res = FALSE;;
	gchar **tokens;
	gchar **tokens_var;
	int i;
	gboolean neg = FALSE;
	SimRuleVar *var;
	int varlevel;
	g_return_val_if_fail (rule != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (text != NULL, FALSE);
	g_return_val_if_fail (field_type != NULL, FALSE);
	g_return_val_if_fail ((inx = sim_text_field_get_index (field_type)) != -1, FALSE);
	/* First , check level. Variable must not be in level 1*/
	if (level == 1) return FALSE;
	/* Check SYNTAX */
	if (text[0] == '!'){
		if (text[1] != '\0')
			p = &text[1];
		else
			return FALSE;
		// Negate
		neg = TRUE;
	}else{
		p = text;
	}
	/* Now check for variable*/
	tokens_var = g_strsplit (p, SIM_DELIMITER_LEVEL, 2);
	varlevel = strtol (tokens_var[0], NULL, 10);
	if (varlevel == 0 && errno == EINVAL){
		g_message ("Bad level in VARIABLE:'%s",text);
		return FALSE;
	}
	var = g_new0 (SimRuleVar, 1);
	if (var){
		var->type = sim_get_rule_var_from_char (tokens_var[1]);
		if (var->type == SIM_RULE_VAR_GENERIC_TEXT)
			var->varIndex = sim_text_field_get_var_index (tokens_var[1]);
		var->attr = inx; // Attribute that this variables refers to
		var->level = varlevel;
		var->negated = neg;
		g_log (G_LOG_DOMAIN,G_LOG_LEVEL_DEBUG,"%s: Variable created var->type = %u var->attr = %u var->varIndex = %u",
	
			__FUNCTION__,var->type,var->attr, var->varIndex); 
		sim_rule_append_var (rule, var);
		res = TRUE;
		
	}
	return res;
}


static gboolean 
sim_xml_directive_set_rule_generic (SimRule *rule,
																				gchar 	*value,
																				gchar 	*field_type,
																				int level){
	gchar *next_token = NULL;
	enum SimTextMatchType matchtype;
	int i;
	g_return_val_if_fail (rule != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (value != NULL, FALSE);
	g_return_val_if_fail (field_type != NULL, FALSE);
	if (strcmp (value,"ANY") != 0){
	for (i=0; text_field_search_type[i].token != NULL && next_token == NULL; i++){
		int inx;
		inx = strlen (text_field_search_type[i].token);
		if (strncmp (text_field_search_type[i].token, value, inx) == 0){
			next_token = &value[inx];
			matchtype = text_field_search_type[i].type;
		}
	}
	}else{
		matchtype = SimMatchTextAny;
		return sim_rule_set_match_any (rule, sim_text_field_get_index (field_type));
	}
	if (next_token){
		switch (matchtype){
			case SimMatchTextEqual:
					return sim_xml_directive_set_rule_exact (rule, next_token, field_type);
				break;
			case SimMatchTextSubstr:
					return sim_xml_directive_set_rule_substr (rule, next_token, field_type);
				break;
			case SimMatchTextRegex:
					return sim_xml_directive_set_rule_regex (rule, next_token, field_type);
				break;
			case SimMatchPrevious:
					return sim_xml_directive_set_rule_var_match (rule, next_token, field_type, level);
				break;
		}
		
	}
	return FALSE;
}

/*
 *	We will group the following keywords in this function:
 *  filename, username, password, userdata1, userdata2.....userdata9
 */
#if 0
static gboolean
sim_xml_directive_set_rule_generic (SimRule          *rule,
																		gchar            *value,
																		gchar						*field_type,int depth) // field_type =PROPERTY_FILENAME, PROPERTY_SRC_IP.... 
{
  gchar     **values;
  gchar     **level;
  gint        i;
	gboolean		field_neg = FALSE; 
  gchar		    *token_value; //this will be each one of the strings, between "," and "," from value.

  g_return_val_if_fail (rule != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (value != NULL, FALSE);
	return sim_xml_directive_set_rule_generic_new (rule, value, field_type, depth);
  values = g_strsplit (value, SIM_DELIMITER_LIST, 0);		
  for (i = 0; values[i] != NULL; i++)
  {
    if (values[i][0] == '!')													
    {
      field_neg = TRUE;
      token_value = values[i]+1;															//removing the "!"...
    }
    else
    {
      field_neg = FALSE;
      token_value = values[i];
    }

    if (strstr (token_value, SIM_DELIMITER_LEVEL))            //if this token doesn't refers to the 1st rule level...
		{
		  SimRuleVar *var = g_new0 (SimRuleVar, 1);							

			level = g_strsplit (token_value, SIM_DELIMITER_LEVEL, 0);	//separate into 2 tokens

		  var->type = sim_get_rule_var_from_char (level[1]);			
			var->attr = sim_xml_directive_get_rule_var_from_property (field_type);
			if (var->attr == SIM_RULE_VAR_NONE)
				return FALSE;
			if (sim_string_is_number (level[0], 0))
				var->level = atoi(level[0]);
			else
			{
				g_strfreev (level);
				g_strfreev (values);
				g_free (var);				
				return FALSE;
			}				
	 
      if (field_neg)
        var->negated = TRUE;
      else
        var->negated = FALSE;
 
		  sim_rule_append_var (rule, var);												
		  g_strfreev (level);																			
		}																													
    else																							
		if (!strcasecmp (token_value, SIM_IN_ADDR_ANY_CONST)) 
		{
      if (field_neg) //we can't negate "ANY" 
      {
        g_strfreev (values);
        return FALSE;
      }
			//this "generic" function is valid only for the keywords filename, username, userdata1...userdata9
			sim_rule_append_generic (rule, token_value, sim_xml_directive_get_rule_var_from_property (field_type));
		}
    else																											// this token IS the 1st level
		{
    	if (field_neg)
	      sim_rule_append_generic_not (rule, token_value, sim_xml_directive_get_rule_var_from_property (field_type));
      else
		    sim_rule_append_generic (rule, token_value, sim_xml_directive_get_rule_var_from_property (field_type));
		}
  }

	return TRUE;
}
#endif

/*
 * Checks all the plugin_sids from a "rule" statment in a directive, and store it in a list in rule->_priv->plugin_sids
 *
 * Returns FALSE on error
 *
 */
static gboolean
sim_xml_directive_set_rule_plugin_sids (SimXmlDirective  *xmldirect, //FIXME: xmldirect is not used in this function
																				SimRule          *rule,
																				gchar            *value)
{
  gchar     **values;
  gchar     **level;
  gint        i;
	gboolean		pluginsid_neg = FALSE; //if the address is negated, this will be YES (just for that sid, not the others).
  gchar		    *token_value; //this will be each one of the strings, between "," and "," from value.

  g_return_val_if_fail (xmldirect != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), FALSE);
  g_return_val_if_fail (rule != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (value != NULL, FALSE);

  values = g_strsplit (value, SIM_DELIMITER_LIST, 0);				//separate each of the individual plugin_sid delimited with ","
  for (i = 0; values[i] != NULL; i++)
  {
    if (values[i][0] == '!')													//Each plugin_sid could be negated. We'll store it in other place.
    {
      pluginsid_neg = TRUE;
      token_value = values[i]+1;															//removing the "!"...
    }
    else
    {
      pluginsid_neg = FALSE;
      token_value = values[i];
    }

    if (strstr (token_value, SIM_DELIMITER_LEVEL))            //if this token doesn't refers to the 1st rule level...
		{
		  SimRuleVar *var = g_new0 (SimRuleVar, 1);								//here is stored the level to wich this PLUGIN_SID make 
																															//reference and what kind of token is (src_ia, protocol...)

			level = g_strsplit (token_value, SIM_DELIMITER_LEVEL, 0);	//separate the (ie) 1:PLUGIN_SID into 2 tokens

		  var->type = sim_get_rule_var_from_char (level[1]);			//level[1] = PLUGIN_SID
		  var->attr = SIM_RULE_VAR_PLUGIN_SID;
			if (sim_string_is_number (level[0], 0))
				var->level = atoi(level[0]);
			else
			{
				g_strfreev (level);
				g_free (var);				
				return FALSE;
			}				
	 
      if (pluginsid_neg)
        var->negated = TRUE;
      else
        var->negated = FALSE;
 
		  sim_rule_append_var (rule, var);												//we don't need to call to sim_rule_append_plugin_sid()
		  g_strfreev (level);																			//because we aren't going to store nothing as we will read
		}																													//the plugin_sid from other level.
    else																							
		if (!strcasecmp (token_value, SIM_IN_ADDR_ANY_CONST)) 
		{
      if (pluginsid_neg) //we can't negate "ANY" plugin_sid!
      {
        g_strfreev (values);
        return FALSE;
      }
		  sim_rule_append_plugin_sid (rule, 0);
		}
    else																											// this token IS the 1st level
		if (sim_string_is_number (token_value, 0))
		{
    	if (pluginsid_neg)
				sim_rule_append_plugin_sid_not (rule, atoi(token_value));
      else
				sim_rule_append_plugin_sid (rule, atoi(token_value));
		}
		else
		{
		  g_strfreev (values);
			return FALSE;
		}
  }
  g_strfreev (values);

	return TRUE;
}

/*
 *
 *
 *
 *
 */
static gboolean
sim_xml_directive_set_rule_src_ips (SimXmlDirective  *xmldirect,	//FIXME: xmldirect is used just to get the container
																    SimRule          *rule,				//so the right way is to pass just the container.
																    gchar            *value)
{
  SimContainer  *container;
  SimNet        *net;
  gchar		      **values;
  gchar			    *token_value; //this will be each one of the strings, between "," and "," from value.
	gboolean			addr_neg;	//if the address is negated, this will be YES (just for that address, not the others).
  gchar				  **level;
  gint          i;

  g_return_val_if_fail (xmldirect != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), FALSE);
  g_return_val_if_fail (rule != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (value != NULL, FALSE);

  container = xmldirect->_priv->container;

  values = g_strsplit (value, SIM_DELIMITER_LIST, 0);						//split into ","...
  for (i = 0; values[i] != NULL; i++)
  {
    if (values[i][0] == '!')													
		{
			addr_neg = TRUE;
			token_value = values[i]+1;	
		}
		else
		{
			addr_neg = FALSE;
			token_value = values[i];
		}
			
    if (strstr (token_value, SIM_DELIMITER_LEVEL))								//if this isn't the first level...
		{
		  SimRuleVar *var = g_new0 (SimRuleVar, 1);

		  level = g_strsplit (token_value, SIM_DELIMITER_LEVEL, 0);

		  var->type = sim_get_rule_var_from_char (level[1]);
		  var->attr = SIM_RULE_VAR_SRC_IA;
      if (sim_string_is_number (level[0], 0))
        var->level = atoi(level[0]);
      else
      {
        g_strfreev (level);
        g_free (var);
        return FALSE;
      }
	
      if (addr_neg)
        var->negated = TRUE;
      else
        var->negated = FALSE;
  
		  sim_rule_append_var (rule, var);

			g_strfreev (level);
		}
    else
		if (!strcasecmp (token_value, SIM_IN_ADDR_ANY_CONST))
		{
			if (addr_neg)	//we can't negate "ANY" address!
			{
			  g_strfreev (values);
				return FALSE;
			}
		  gchar *ip = g_strdup (SIM_IN_ADDR_ANY_IP_STR);
			sim_rule_append_src_inet (rule, sim_inet_new (ip));			
		  g_free (ip);
		}
    else 
		if (!strcasecmp (token_value, SIM_HOME_NET_CONST))						//usually, "HOME_NET"
    {
      /* load all nets as source
         Todo: load only those flagged as "internal". 
      */
      GList *nets = sim_container_get_nets(container);
      while (nets)
      {
	      SimNet *net = (SimNet *) nets->data;
		    GList *inets = sim_net_get_inets (net); 						//all the nets in src Policy
			  while(inets)
				{
          SimInet *inet = (SimInet *) inets->data;
					if (addr_neg)	//if the address inside the rule is negated, the we have to add it to the src_inet_not Glist
	          sim_rule_append_src_inet_not (rule, inet);
					else
						sim_rule_append_src_inet (rule, inet);
          inets = inets->next;
        }
        g_list_free(inets);
        nets = nets->next;
      }
      g_list_free(nets);
    }
    else																											// ossim acepts too network names defined in Policy.
		{
		  net = (SimNet *) sim_container_get_net_by_name (container, token_value);
			if (net)
	    {
	      GList *inets = sim_net_get_inets (net);
				if (inets)
		      while (inets)
					{
					  SimInet *inet = (SimInet *) inets->data;
	          if (addr_neg)
  	          sim_rule_append_src_inet_not (rule, inet);
    	      else
      	      sim_rule_append_src_inet (rule, inet);
					  inets = inets->next;
					}
				else
					return FALSE;
			}
		  else																										//and of course, we accept a single network.
	    {
	      GList *inets = sim_get_inets (token_value);
				if (inets)
		      while (inets)
					{
						SimInet *inet = (SimInet *) inets->data;
	          if (addr_neg)
  	          sim_rule_append_src_inet_not (rule, inet);
    	      else
      	      sim_rule_append_src_inet (rule, inet);
					  inets = inets->next;
					}
				else
					return FALSE;
	    }
		}
  }

  g_strfreev (values);
	return TRUE;
}

/*
 *
 *
 *
 *
 */
static gboolean
sim_xml_directive_set_rule_dst_ips (SimXmlDirective  *xmldirect,
																    SimRule          *rule,
																    gchar            *value)
{
  SimContainer  *container;
  SimNet     *net;
  gchar     	**values;
	gchar				*token_value;
	gboolean 		addr_neg;
  gchar     	**level;
  gint        i;

  g_return_val_if_fail (xmldirect != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), FALSE);
  g_return_val_if_fail (rule != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (value != NULL, FALSE);

  container = xmldirect->_priv->container;

  values = g_strsplit (value, SIM_DELIMITER_LIST, 0);
  for (i = 0; values[i] != NULL; i++)
  {
    if (values[i][0] == '!')													
    {
      addr_neg = TRUE;
      token_value = values[i]+1;
    }
    else
    {
      addr_neg = FALSE;
      token_value = values[i];
    }

    if (strstr (token_value, SIM_DELIMITER_LEVEL))
		{
		  SimRuleVar *var = g_new0 (SimRuleVar, 1);

	  	level = g_strsplit (token_value, SIM_DELIMITER_LEVEL, 0);

		  var->type = sim_get_rule_var_from_char (level[1]);
		  var->attr = SIM_RULE_VAR_DST_IA;
      if (sim_string_is_number (level[0], 0))
        var->level = atoi(level[0]);
      else
      {
        g_strfreev (level);
        g_free (var);
        return FALSE;
      }
	
      if (addr_neg)
        var->negated = TRUE;
      else
        var->negated = FALSE;
  
		  sim_rule_append_var (rule, var);
	  	g_strfreev (level);
		}
    else
		if (!strcasecmp (token_value, SIM_IN_ADDR_ANY_CONST))
		{
			if (addr_neg)	//we can't negate "ANY" address!
			{
			  g_strfreev (values);
				return FALSE;
			}
		  gchar *ip = g_strdup (SIM_IN_ADDR_ANY_IP_STR);
      sim_rule_append_dst_inet (rule, sim_inet_new (ip));
		  g_free (ip);
		}
    else
		if (!strcasecmp (token_value, SIM_HOME_NET_CONST))
    {
      /* load all nets as destination
         Todo: load only those flagged as "internal". 
      */
      GList *nets = sim_container_get_nets(container);
      while(nets)
      {
    	  SimNet *net = (SimNet *) nets->data;
        GList *inets = sim_net_get_inets (net);
        while(inets)
				{
          SimInet *inet = (SimInet *) inets->data;
          if (addr_neg)
            sim_rule_append_dst_inet_not (rule, inet);
          else
            sim_rule_append_dst_inet (rule, inet);
          inets = inets->next;
        }
        g_list_free(inets);
        nets = nets->next;
      }
      g_list_free(nets);
    }
    else
		{
	  	net = (SimNet *) sim_container_get_net_by_name (container, token_value);
		  if (net)
	    {
	      GList *inets = sim_net_get_inets (net);
				if (inets)
		      while (inets)
					{
			  		SimInet *inet = (SimInet *) inets->data;
	          if (addr_neg)
  	          sim_rule_append_dst_inet_not (rule, inet);
    	      else
      	      sim_rule_append_dst_inet (rule, inet);
				  	inets = inets->next;
					}
				else
					return FALSE;
	    }
	  	else
	    {
	      GList *inets = sim_get_inets (token_value);
				if (inets)
		      while (inets)
					{
					  SimInet *inet = (SimInet *) inets->data;
	          if (addr_neg)
  	          sim_rule_append_dst_inet_not (rule, inet);
    	      else
      	      sim_rule_append_dst_inet (rule, inet);
		  		  inets = inets->next;
					}
				else
					return FALSE;
	    }
		}
  }
  g_strfreev (values);
	return TRUE;
}

/*
 *
 *
 *
 *
 */
static gboolean
sim_xml_directive_set_rule_src_ports (SimXmlDirective  *xmldirect,
																      SimRule          *rule,
																      gchar            *value)
{
  SimContainer  *container;
  SimNet     *net;
  GList      *hosts;
  gchar     **values;
  gchar     **level;
  gchar     **range;
  gchar      *host;
  gint        i;
	gchar 		*token_value;
	gboolean	port_neg;

  g_return_val_if_fail (xmldirect != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), FALSE);
  g_return_val_if_fail (rule != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (value != NULL, FALSE);

  container = xmldirect->_priv->container;

  values = g_strsplit (value, SIM_DELIMITER_LIST, 0);
  for (i = 0; values[i] != NULL; i++)
  {
    if (values[i][0] == '!')													
    {
      port_neg = TRUE;
      token_value = values[i]+1;
    }
    else
    {
      port_neg = FALSE;
      token_value = values[i];
    }

    if (strstr (token_value, SIM_DELIMITER_LEVEL))
		{
		  SimRuleVar *var = g_new0 (SimRuleVar, 1);
		  level = g_strsplit (token_value, SIM_DELIMITER_LEVEL, 0);

		  var->type = sim_get_rule_var_from_char (level[1]);
	  	var->attr = SIM_RULE_VAR_SRC_PORT;
      if (sim_string_is_number (level[0], 0))
        var->level = atoi(level[0]);
      else
      {
        g_strfreev (level);
        g_strfreev (values);
        g_free (var);
        return FALSE;
      }

      if (port_neg)
        var->negated = TRUE;
      else
        var->negated = FALSE;

		  sim_rule_append_var (rule, var);

	  	g_strfreev (level);
		}
    else
		if (!strcasecmp (token_value,  SIM_IN_ADDR_ANY_CONST))
		{
      if (port_neg) //we can't negate "ANY" port!
      {
        g_strfreev (values);
        return FALSE;
      }
		  sim_rule_append_src_port (rule, 0);
		}
    else
		if (strstr (token_value, SIM_DELIMITER_RANGE))							//multiple ports in a range. "1-5"
    {
      gint start, end, j = 0;

      range = g_strsplit (token_value, SIM_DELIMITER_RANGE, 0);

			if (!sim_string_is_number (range[0], 0) || !sim_string_is_number (range[1], 0))
			{
				g_strfreev (range);
				g_strfreev (values);
				return FALSE;
			}

      start = atoi(range[0]);
      end   = atoi(range[1]);

      for(j=start;j<=end;j++)
			{
				if (port_neg)			//if ports are !1-5, all the ports in that range will be negated.
				  sim_rule_append_src_port_not (rule, j);
				else
				  sim_rule_append_src_port (rule, j);
      }
      g_strfreev (range); 
    }
    else																									//just one port
		{
      if (sim_string_is_number (token_value, 0))
			{
				if (port_neg)			
				  sim_rule_append_src_port_not (rule, atoi (token_value));
				else
				  sim_rule_append_src_port (rule, atoi (token_value));
			}
      else
			{
				g_strfreev (values);
        return FALSE;
			}
		}
  }
  g_strfreev (values);
	return TRUE;
}

/*
 *
 *
 *
 *
 */
static gboolean
sim_xml_directive_set_rule_dst_ports (SimXmlDirective  *xmldirect,
																      SimRule          *rule,
																      gchar            *value)
{
  SimContainer  *container;
  SimNet     *net;
  GList      *hosts;
  gchar     **values;
  gchar     **level;
  gchar     **range;
  gchar      *host;
  gint        i;
	gchar 		*token_value;
	gboolean	port_neg;

  g_return_val_if_fail (xmldirect != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), FALSE);
  g_return_val_if_fail (rule != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (value != NULL, FALSE);

  container = xmldirect->_priv->container;

  values = g_strsplit (value, SIM_DELIMITER_LIST, 0);
  for (i = 0; values[i] != NULL; i++)
  {
    if (values[i][0] == '!')													
    {
      port_neg = TRUE;
      token_value = values[i]+1;
    }
    else
    {
      port_neg = FALSE;
      token_value = values[i];
    }

    if (strstr (token_value, SIM_DELIMITER_LEVEL))
		{
		  SimRuleVar *var = g_new0 (SimRuleVar, 1);

	  	level = g_strsplit (token_value, SIM_DELIMITER_LEVEL, 0);

		  var->type = sim_get_rule_var_from_char (level[1]);
		  var->attr = SIM_RULE_VAR_DST_PORT;
      if (sim_string_is_number (level[0], 0))
        var->level = atoi(level[0]);
      else
      {
        g_strfreev (level);
        g_free (var);
        g_strfreev (values);
        return FALSE;
      }
			
			if (port_neg)
				var->negated = TRUE;
			else
				var->negated = FALSE;
	
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_xml_directive_set_rule_dst_ports: rule name: %s",sim_rule_get_name(rule));
		  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_xml_directive_set_rule_dst_ports: type: %d",var->type);
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_xml_directive_set_rule_dst_ports: attr: %d",var->attr);
			g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "sim_xml_directive_set_rule_dst_ports: negated: %d",var->negated);
			
		  sim_rule_append_var (rule, var);

		  g_strfreev (level);
		}
    else
		if (!strcasecmp (token_value, SIM_IN_ADDR_ANY_CONST))
		{
      if (port_neg) //we can't negate "ANY" port!
      {
        g_strfreev (values);
        return FALSE;
      }
      sim_rule_append_dst_port (rule, 0);
		}
    else
		if (strstr (token_value, SIM_DELIMITER_RANGE))
    {
      gint start, end, j = 0;

      range = g_strsplit (token_value, SIM_DELIMITER_RANGE, 0);
      if (!sim_string_is_number (range[0], 0) || !sim_string_is_number (range[1], 0))
      {
        g_strfreev (range);
        g_strfreev (values);
        return FALSE;
      }

      start = atoi(range[0]);
      end   = atoi(range[1]);

      for(j=start;j<=end;j++)
			{
        if (port_neg)     //if ports are ie. !1-5, all the ports in that range will be negated.
          sim_rule_append_dst_port_not (rule, j);
        else
          sim_rule_append_dst_port (rule, j);
      }
      g_strfreev (range); 
    }
    else
		{
			if (sim_string_is_number (token_value, 0))
			{
        if (port_neg)
          sim_rule_append_dst_port_not (rule, atoi (token_value));
        else
          sim_rule_append_dst_port (rule, atoi (token_value));
			}
			else
			{
  			g_strfreev (values);
				return FALSE;
			}
		}
  }
  g_strfreev (values);
	return TRUE;
}

/*
 *
 *
 *
 *
 */
static gboolean
sim_xml_directive_set_rule_protocol (SimXmlDirective  *xmldirect,		//FIXME: xmldirect not needed here
																     SimRule          *rule,
																     gchar            *value)
{
  gchar     **values;
  gchar     **level;
  gint        i;
	gchar 		*token_value;
	gboolean	proto_neg;

  g_return_val_if_fail (xmldirect != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), FALSE);
  g_return_val_if_fail (rule != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (value != NULL, FALSE);

  values = g_strsplit (value, SIM_DELIMITER_LIST, 0);
  for (i = 0; values[i] != NULL; i++)
  {
    if (values[i][0] == '!')													
    {
      proto_neg = TRUE;
      token_value = values[i]+1;
    }
    else
    {
      proto_neg = FALSE;
      token_value = values[i];
    }

    if (strstr (token_value, SIM_DELIMITER_LEVEL))
		{
		  SimRuleVar *var = g_new0 (SimRuleVar, 1);

		  level = g_strsplit (token_value, SIM_DELIMITER_LEVEL, 0);

		  var->type = sim_get_rule_var_from_char (level[1]);
		  var->attr = SIM_RULE_VAR_PROTOCOL;
      if (sim_string_is_number (level[0], 0))
        var->level = atoi(level[0]);
      else
      {
        g_strfreev (level);
			  g_strfreev (values);
        g_free (var);
        return FALSE;
      }
	 
      if (proto_neg)
        var->negated = TRUE;
      else
        var->negated = FALSE;
 
		  sim_rule_append_var (rule, var);

	  	g_strfreev (level);
		}
    else
		if (!strcasecmp (token_value, SIM_IN_ADDR_ANY_CONST)) 
		{
    	if (proto_neg) //we can't negate "ANY" protocol
      {
        g_strfreev (values);
        return FALSE;
      }
      sim_rule_append_protocol (rule, 0);
		}
    else
		{
      if (sim_string_is_number (token_value, 0))
      {
				if (proto_neg)
					sim_rule_append_protocol_not (rule, atoi (token_value));
				else
					sim_rule_append_protocol (rule, atoi (token_value));
			}
      else
      {
				int proto = sim_protocol_get_type_from_str (token_value);
				if (proto  != SIM_PROTOCOL_TYPE_NONE)
				{
					if (proto_neg)
			      sim_rule_append_protocol_not (rule, proto );
					else
						sim_rule_append_protocol (rule, proto);
				}
				else
				{
				  g_strfreev (values);
					return FALSE;
				}
      }
		}
  }
  g_strfreev (values);
	return TRUE;
}

/*
 *
 *
 *
 *
 */
static gboolean
sim_xml_directive_set_rule_sensors (SimContainer	  *container,	
																    SimRule          *rule,				
																    gchar            *value)
{
  SimNet        *net;
  SimSensor     *sensor;
  gchar        **values;
  gchar        **level;
  gint           i;
  gchar     *token_value;
  gboolean  sensor_neg;

  g_return_val_if_fail (container != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_CONTAINER (container), FALSE);
  g_return_val_if_fail (rule != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (value != NULL, FALSE);

  values = g_strsplit (value, SIM_DELIMITER_LIST, 0);						//split into ","...
  for (i = 0; values[i] != NULL; i++)
  {
    if (values[i][0] == '!')													
    {
      sensor_neg = TRUE;
      token_value = values[i]+1;
    }
    else
    {
      sensor_neg = FALSE;
      token_value = values[i];
    }

    if (strstr (token_value, SIM_DELIMITER_LEVEL))								//if this isn't the first level...
		{
		  SimRuleVar *var = g_new0 (SimRuleVar, 1);

		  level = g_strsplit (token_value, SIM_DELIMITER_LEVEL, 0);

		  var->type = sim_get_rule_var_from_char (level[1]);
		  var->attr = SIM_RULE_VAR_SENSOR;
      if (sim_string_is_number (level[0], 0))
        var->level = atoi(level[0]);
      else
      {
        g_strfreev (level);
  			g_strfreev (values);
        g_free (var);
        return FALSE;
      }
	 
      if (sensor_neg)
        var->negated = TRUE;
      else
        var->negated = FALSE;
 
		  sim_rule_append_var (rule, var);

			g_strfreev (level);
		}
    else
		if (!strcasecmp (token_value, SIM_IN_ADDR_ANY_CONST))
		{
      if (sensor_neg) //we can't negate "ANY" sensor
      {
        g_strfreev (values);
        return FALSE;
      }
		  gchar *ip = g_strdup (SIM_IN_ADDR_ANY_IP_STR);
			sim_rule_append_sensor (rule, sim_sensor_new_from_hostname (ip));
		  g_free (ip);
		}
    else																											// ossim accepts too sensor names defined in policy
		{
		  sensor = (SimSensor *) sim_container_get_sensor_by_name (container, token_value);
			if (sensor)
	    {
				if (sensor_neg)
					sim_rule_append_sensor_not (rule, sensor);
				else
					sim_rule_append_sensor (rule, sensor);
			}
		  else																										//and of course, we accept a single sensor
	    {
				sensor = sim_sensor_new_from_hostname (token_value);
				if (sensor)
				{
	        if (sensor_neg)
  	        sim_rule_append_sensor_not (rule, sensor);
    	    else
      	    sim_rule_append_sensor (rule, sensor);
				}
				else
				{
  				g_strfreev (values);
					return FALSE;					
				}
	    }
		}
  }

  g_strfreev (values);
	return TRUE;
}


/*
 * Create a GNode element
 *
 * Returns NULL on error.
 *
 * GNode *root: first  time this should be called with NULL. after that, recursively 
 * it will pass the pointer to the node.
 *
 * FIXME: I don't like this (an other) function(s). Each time I want to add a new keyword is necessary to modify
 * lots of thins. This could be done with some kind of table where is just needed to add a new keyword and 
 * it's type to get it inserted in directives. The major absurdity is the needed to use 9 (9!!!) functions to 
 * store the values from userdata1, userdata2... instead a single function and table where it points to the right variable.
 */
GNode*
sim_xml_directive_new_rule_from_node (SimXmlDirective  *xmldirect,
																      xmlNodePtr        node,
																      GNode            *root,
																      gint              level)
{
  SimRuleType    type = SIM_RULE_TYPE_NONE;
  SimRule       *rule;
  SimAction     *action;
  GNode         *rule_node;
  GNode         *rule_child;
  xmlNodePtr     children;
  xmlNodePtr     children_rules;
  xmlNodePtr     actions;
  gchar         *value = NULL;
  gchar         *name = NULL;
  SimConditionType   condition = SIM_CONDITION_TYPE_NONE;
  gchar         *par_value = NULL;
  gint           interval = 0;
  gboolean       absolute = FALSE;
  gboolean       sticky = FALSE;
  gint           sticky_different = SIM_RULE_VAR_NONE;
  gboolean       not = FALSE;
  gint           priority = 1;
  gint           reliability = 1;
  gboolean       rel_abs = TRUE;
  gint           time_out = 0;
  gint           occurrence = 1;
  gint           plugin = 0;
  gint           tplugin = 0;
	gchar					*filename = NULL;
	gchar					*username = NULL;
	gchar					*password = NULL;
	gchar					*userdata1 = NULL;
	gchar					*userdata2 = NULL;
	gchar					*userdata3 = NULL;
	gchar					*userdata4 = NULL;
	gchar					*userdata5 = NULL;
	gchar					*userdata6 = NULL;
	gchar					*userdata7 = NULL;
	gchar					*userdata8 = NULL;
	gchar					*userdata9 = NULL;

  g_return_val_if_fail (xmldirect != NULL, NULL);
  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), NULL);
  g_return_val_if_fail (node != NULL, NULL);

  if (strcmp ((gchar *) node->name, OBJECT_RULE)) //This must be a "rule" always.
  {
    g_message ("Invalid rule node %s", node->name);
    return NULL;
  }
 
	//now we're going to extract all the data from the node and store it into variables so we can later return it.
	//This node can be all the rules inside directive (the first time it enters in this function) or just an 
	//internal "rule" thanks to the recursive programming
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_TYPE)))
  {
    if (!g_ascii_strcasecmp (value, "detector"))
			type = SIM_RULE_TYPE_DETECTOR;
    else 
		if (!g_ascii_strcasecmp (value, "monitor"))
			type = SIM_RULE_TYPE_MONITOR;
    else
			type = SIM_RULE_TYPE_NONE;

    xmlFree(value);

    if (type == SIM_RULE_TYPE_NONE)
    {
      g_message("Error. there is a problem at the 'type' field in the directive");
      return NULL;
    }

  }
	
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_STICKY)))
  {
    if (!g_ascii_strcasecmp (value, "TRUE"))
			sticky = TRUE;
    xmlFree(value);
  } 
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_STICKY_DIFFERENT)))
  {
    sticky_different = sim_get_rule_var_from_char (value);
    xmlFree(value);
  } 
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_NOT)))
  {
    if (!g_ascii_strcasecmp (value, "TRUE"))
			not = TRUE;
    xmlFree(value);
  } 
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_NAME)))
  { 
    name = g_strdup (value);
    xmlFree(value);
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_PRIORITY)))
  {
 		if (sim_string_is_number (value, 0))
	    priority= strtol(value, (char **) NULL, 10);
		else
		{
    	xmlFree(value);
			g_message("Error. there is a problem at the Priority field");
			return NULL;
		}
    xmlFree(value);
  } 
		
	if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_RELIABILITY)))
  {
		gboolean aux=TRUE;
		gchar *tempi = value;	//we don't wan't to loose the pointer.....	
		if (value[0] == '+')
		{
			rel_abs = FALSE;
			value++;		// ++ to the pointer so now "value" points to the number string and we can check it.
   		if (sim_string_is_number (value, 0))
	 	  	reliability = atoi(value);
			else
				aux=FALSE;			
		}
		else
		{
   		if (sim_string_is_number (value, 0))
      	reliability = atoi(value);
			else
				aux=FALSE;
		}
		value = tempi;
		xmlFree(value);
		if (aux == FALSE)
		{
			g_message("Error. there is a problem at the Reliability field");
			return NULL;
		}
	}

	if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_CONDITION)))
  { 
    condition = sim_condition_get_type_from_str (value);
    xmlFree(value);
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_VALUE)))
  { 
    par_value = g_strdup (value);
    xmlFree(value);
  } 
	if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_INTERVAL)))
  {
    if (sim_string_is_number (value, 0))
		{
	    interval = strtol(value, (char **) NULL, 10);
	    xmlFree(value);
		}
		else
		{
	    xmlFree(value);
			g_message("Error. there is a problem at the Interval field");
			return NULL;
		}
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_ABSOLUTE)))
  {
    if (!g_ascii_strcasecmp (value, "TRUE"))
			absolute = TRUE;
    xmlFree(value);
  } 
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_TIME_OUT)))
  {
    if (sim_string_is_number (value, 0))
    {
      time_out = strtol(value, (char **) NULL, 10);
      xmlFree(value);
    }
    else
    {
      xmlFree(value);
			g_message("Error. there is a problem at the 'Absolute' field");
      return NULL;
    }
  }
	
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_OCCURRENCE)))
  {
    if (sim_string_is_number (value, 0))
    {
      occurrence = strtol(value, (char **) NULL, 10);
      xmlFree(value);
    }
    else
    {
      xmlFree(value);
			g_message("Error. there is a problem at the Occurrence field");
      return NULL;
    }
  }
	
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_PLUGIN_ID)))
  {
    if (sim_string_is_number (value, 0))
    {
      plugin = strtol(value, (char **) NULL, 10);
      xmlFree(value);
    }
    else
    {
		  g_log (G_LOG_DOMAIN, G_LOG_LEVEL_DEBUG, "Error: plugin-id: %s",value);
			xmlFree(value);
			g_message("Error. there is a problem at the Plugin_id field");
      return NULL;
    }
  }

  rule = sim_rule_new ();
  rule->type = type;
  if (sticky) 
		sim_rule_set_sticky (rule, sticky);
  if (sticky_different) 
		sim_rule_set_sticky_different (rule, sticky_different);
  if (not) 
		sim_rule_set_not (rule, not);
  sim_rule_set_level (rule, level);
  sim_rule_set_name (rule, name);
  sim_rule_set_priority (rule, priority);
  sim_rule_set_reliability (rule, reliability);
  sim_rule_set_rel_abs (rule, rel_abs);
  sim_rule_set_condition (rule, condition);
  if (par_value) 
		sim_rule_set_value (rule, par_value);
  if (interval > 0) 
		sim_rule_set_interval (rule, interval);
  if (absolute) 
		sim_rule_set_absolute (rule, absolute);
  sim_rule_set_time_out (rule, time_out);
  sim_rule_set_occurrence (rule, occurrence);
  sim_rule_set_plugin_id (rule, plugin);
	
	//at this moment, "rule" variable has some properties, and we continue filling it.
	//Now, we have to fill the properties that can handle multiple variables, like sids, or src_ips ie.
	if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_PLUGIN_SID)))
  {
    if (sim_xml_directive_set_rule_plugin_sids (xmldirect, rule, value)) //FIXME: xmldirect is not needed in the function
			xmlFree(value);
		else
		{
			g_message("Error. there is a problem at the Plugin_sid field");
			return NULL;			
		}
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_SRC_IP)))
  {
    if (sim_xml_directive_set_rule_src_ips (xmldirect, rule, value))
	    xmlFree(value);
		else
		{
			g_message("Error. there is a problem at the src_ip field");
			return NULL;			
		}
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_DST_IP)))
  {
    if (sim_xml_directive_set_rule_dst_ips (xmldirect, rule, value))
			xmlFree(value);
		else
		{
			g_message("Error. there is a problem at the dst_ip field");
			return NULL;			
		}
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_SRC_PORT)))
  {
    if (sim_xml_directive_set_rule_src_ports (xmldirect, rule, value))
		  xmlFree(value);
		else
		{
			g_message("Error. there is a problem at the src_port field");
			return NULL;		
		}
  }

  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_DST_PORT)))
  {
    if (sim_xml_directive_set_rule_dst_ports (xmldirect, rule, value))
	    xmlFree(value);		
		else
		{
			g_message("Error. there is a problem at the dst_port field");
		  return NULL;				
		}
  }
/*			g_message("---------------------");
  sim_rule_print(rule);
			g_message("---------------------");
*/
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_PROTOCOL)))
  {
    if (sim_xml_directive_set_rule_protocol (xmldirect, rule, value))
	    xmlFree(value);
    else
		{
			g_message("Error. there is a problem at the Protocol field");
		  return NULL;		
		}
  }

	if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_SENSOR)))
  {
    if (sim_xml_directive_set_rule_sensors (xmldirect->_priv->container, rule, value))
      xmlFree(value);
    else
		{
			g_message("Error. there is a problem at the Sensor field");
      return NULL;
		}
  }
	
	if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_FILENAME)))
  {
	    if (sim_xml_directive_set_rule_generic (rule, value, PROPERTY_FILENAME,level)) 
      	xmlFree(value);
   	  else
      {
      	xmlFree(value);
      	g_message("Error: there is a problem at the Filename field");
     	  return NULL;
      }
  }

	if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_USERNAME)))
  {
    if (sim_xml_directive_set_rule_generic (rule, value, PROPERTY_USERNAME, level)) 
      xmlFree(value);
    else
    {
      xmlFree(value);
      g_message("Error: there is a problem at the Username field");
      return NULL;
    }
  }

	if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_PASSWORD)))
  {
    if (sim_xml_directive_set_rule_generic (rule, value, PROPERTY_PASSWORD, level)) 
      xmlFree(value);
    else
    {
      xmlFree(value);
      g_message("Error. there is a problem at the Password field");
      return NULL;
    }
  }
	if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_USERDATA1)))
  {
    if (sim_xml_directive_set_rule_generic (rule, value, PROPERTY_USERDATA1, level)) 
      xmlFree(value);
    else
    {
      xmlFree(value);
      g_message("Error. there is a problem at the Userdata1 field");
      return NULL;
    }
  }
	if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_USERDATA2)))
  {
    if (sim_xml_directive_set_rule_generic (rule, value, PROPERTY_USERDATA2, level)) 
      xmlFree(value);
    else
    {
      xmlFree(value);
      g_message("Error. there is a problem at the Userdata2 field");
      return NULL;
    }
  }

	if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_USERDATA3)))
  {
    if (sim_xml_directive_set_rule_generic (rule, value, PROPERTY_USERDATA3, level)) 
      xmlFree(value);
    else
    {
      xmlFree(value);
      g_message("Error. there is a problem at the Userdata3 field");
      return NULL;
    }
  }

	if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_USERDATA4)))
  {
    if (sim_xml_directive_set_rule_generic (rule, value, PROPERTY_USERDATA4,level)) 
      xmlFree(value);
    else
    {
      xmlFree(value);
      g_message("Error. there is a problem at the Userdata4 field");
      return NULL;
    }
  }

	if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_USERDATA5)))
  {
    if (sim_xml_directive_set_rule_generic (rule, value, PROPERTY_USERDATA5,level)) 
      xmlFree(value);
    else
    {
      xmlFree(value);
      g_message("Error. there is a problem at the Userdata5 field");
      return NULL;
    }
  }

	if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_USERDATA6)))
  {
    if (sim_xml_directive_set_rule_generic (rule, value, PROPERTY_USERDATA6, level)) 
      xmlFree(value);
    else
    {
      xmlFree(value);
      g_message("Error. there is a problem at the Userdata6 field");
      return NULL;
    }
  }

	if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_USERDATA7)))
  {
    if (sim_xml_directive_set_rule_generic (rule, value, PROPERTY_USERDATA7, level)) 
      xmlFree(value);
    else
    {
      xmlFree(value);
      g_message("Error. there is a problem at the Userdata7 field");
      return NULL;
    }
  }

	if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_USERDATA8)))
  {
    if (sim_xml_directive_set_rule_generic (rule, value, PROPERTY_USERDATA8, level)) 
      xmlFree(value);
    else
    {
      xmlFree(value);
      g_message("Error. there is a problem at the Userdata8 field");
      return NULL;
    }
  }

	if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_USERDATA9)))
  {
    if (sim_xml_directive_set_rule_generic (rule, value, PROPERTY_USERDATA9, level)) 
      xmlFree(value);
    else
    {
      xmlFree(value);
      g_message("Error. there is a problem at the Userdata9 field");
      return NULL;
    }
  }

  if (!root)												//ok, this is the first  rule, the root node...
    rule_node = g_node_new (rule);	//..so we have to create the first GNode. 
  else
    rule_node = g_node_append_data (root, rule);	//if it's a child node, we append it to the root.

  children = node->xmlChildrenNode;
  while (children) 									//if the node has more nodes (rules), we have to do the same than this function again
  {																	//so we can call this recursively.
    /* Gets Rules Node */
    if (!strcmp ((gchar *) children->name, OBJECT_RULES))
		{
		  children_rules = children->xmlChildrenNode;
			while (children_rules)
	    {
	      /* Recursive call */
	      if (!strcmp ((gchar *) children_rules->name, OBJECT_RULE)) 
				{
					sim_xml_directive_new_rule_from_node (xmldirect, children_rules, rule_node, level + 1); 
	      }

	      children_rules = children_rules->next;
	    }
		}
 
	  children = children->next;
  }

  return rule_node;
}

/*
 *
 *
 *
 *
 */
GList*
sim_xml_directive_get_directives (SimXmlDirective *xmldirect)
{
  g_return_val_if_fail (xmldirect != NULL, NULL);
  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), NULL);

  return xmldirect->_priv->directives;
}


/*
 *
 *
 *
 *
 */
void
sim_xml_directive_new_append_directive_from_node (SimXmlDirective	*xmldirect,
																								  xmlNodePtr		node,
																								  SimDirectiveGroup	*group)
{
  xmlChar	*value;
  gint		id;

  if (strcmp ((gchar *) node->name, OBJECT_APPEND_DIRECTIVE))
  {
    g_message ("Invalid append directive node %s", node->name);
		return;
  }
  if ((value = xmlGetProp (node, (xmlChar *) PROPERTY_DIRECTIVE_ID)))
  {
		if (sim_string_is_number ((gchar *)value, 0))
		{
	    id = strtol((gchar *) value, (char **) NULL, 10);
		  sim_directive_group_append_id (group, id);
		}
		else
			g_message("There is an error in directive groups. The directives ID may be wrong");
		xmlFree(value);
  }
}


/*
 *
 *
 *
 *
 */
gboolean
sim_xml_directive_new_group_from_node (SimXmlDirective	*xmldirect,
																       xmlNodePtr	node)
{
  SimDirectiveGroup	*group;
  xmlNodePtr		children;
  xmlChar		*value;

  if (strcmp ((gchar *) node->name, OBJECT_GROUP))
  {
    g_message ("Invalid group node %s", node->name);
		return FALSE;
  }

  group = sim_directive_group_new ();

  if ((value = xmlGetProp (node, (xmlChar *) PROPERTY_NAME)))
  {
    gchar *name = g_strdup ((gchar *) value);
    sim_directive_group_set_name (group, name);
    xmlFree(value);
  } 

  if ((value = xmlGetProp (node, (xmlChar *) PROPERTY_STICKY)))
  {
    if (!g_ascii_strcasecmp ((gchar *) value, "true"))
			sim_directive_group_set_sticky (group, TRUE);
    else
			sim_directive_group_set_sticky (group, FALSE);
    xmlFree(value);
  }

  children = node->xmlChildrenNode;
  while (children)
  {
    if (!strcmp ((gchar *) children->name, OBJECT_APPEND_DIRECTIVE))
			sim_xml_directive_new_append_directive_from_node (xmldirect, children, group);
  
    children = children->next;
  }

  xmldirect->_priv->groups = g_list_append (xmldirect->_priv->groups, group);
}



/*
 *
 *
 *
 */
gboolean 
sim_xml_directive_new_groups_from_node (SimXmlDirective	*xmldirect,
																				xmlNodePtr	node)
{
  xmlNodePtr  children;

  g_return_if_fail (xmldirect);
  g_return_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect));

  if (strcmp ((gchar *) node->name, OBJECT_GROUPS))
  {
    g_message ("Invalid groups node %s", node->name);
		return FALSE;
  }

  children = node->xmlChildrenNode;
  while (children)
  {
    if (!strcmp ((gchar *) children->name, OBJECT_GROUP))
			sim_xml_directive_new_group_from_node (xmldirect, children);
     
    children = children->next;
  }

}


static gchar *sim_xml_directive_get_search_type (SimRule *rule, int inx,gchar *value){
	int i;
	gchar *res = NULL;
	for (i=0; text_field_search_type[i].token != NULL && res == NULL;i++){
		if (strncmp (text_field_search_type[i].token,value,strlen(text_field_search_type[i].token)) == 0){
			sim_rule_set_text_search (rule , inx, text_field_search_type[i].type);
			res = &value[i];
		}
	}	
	return res;
}
// vim: set tabstop=2:
