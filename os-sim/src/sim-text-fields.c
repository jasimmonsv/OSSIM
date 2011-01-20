#include <glib.h>
#include <assert.h>
#include "sim-text-fields.h"


static struct{
	gchar *name;
	int 	index;
	gchar *varname;
}simtextfields[]={
	{"username",SimTextFieldUsername,"USERNAME"},
	{"password",SimTextFieldPassword,"PASSWORD"},
	{"filename",SimTextFieldFilename,"FILENAME"},
	{"userdata1",SimTextFieldUserdata1,"USERDATA1"},
	{"userdata2",SimTextFieldUserdata2,"USERDATA2"},
	{"userdata3",SimTextFieldUserdata3,"USERDATA3"},
	{"userdata4",SimTextFieldUserdata4,"USERDATA4"},
	{"userdata5",SimTextFieldUserdata5,"USERDATA5"},
	{"userdata6",SimTextFieldUserdata6,"USERDATA6"},
	{"userdata7",SimTextFieldUserdata7,"USERDATA7"},
	{"userdata8",SimTextFieldUserdata8,"USERDATA8"},
	{"userdata9",SimTextFieldUserdata9,"USERDATA9"}
};

inline
int sim_text_field_get_index (const char *s){
	int res = -1;
	int i;
	for (i = 0;i < (sizeof(simtextfields)/sizeof (simtextfields[0])) && res == -1;i++){
		if (strcmp (s,simtextfields[i].name) == 0) res = simtextfields[i].index;
	}
	return res;
}
inline
const gchar *sim_text_field_get_name (guint inx){
	assert (inx < N_TEXT_FIELDS);
	return simtextfields[inx].name;
}
inline
const gchar *sim_text_field_get_var_name (guint inx){
	assert (inx < N_TEXT_FIELDS);
	return simtextfields[inx].varname;
}
inline
int sim_text_field_get_var_index (const char *name){
	int res = -1;
	int i;
	for (i = 0;i < (sizeof(simtextfields)/sizeof (simtextfields[0])) && res == -1;i++){
		if (strcmp (name,simtextfields[i].varname) == 0) res = simtextfields[i].index;
	}
	return res;

}
