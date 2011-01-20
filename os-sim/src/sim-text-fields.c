#include <glib.h>
#include <assert.h>
#include "sim-text-fields.h"


static struct{
	gchar *name;
	int 	index;
}simtextfields[]={
	{"username",SimTextFieldUsername},
	{"password",SimTextFieldPassword},
	{"filename",SimTextFieldFilename},
	{"userdata1",SimTextFieldUserdata1},
	{"userdata2",SimTextFieldUserdata2},
	{"userdata3",SimTextFieldUserdata3},
	{"userdata4",SimTextFieldUserdata4},
	{"userdata5",SimTextFieldUserdata5},
	{"userdata6",SimTextFieldUserdata6},
	{"userdata7",SimTextFieldUserdata7},
	{"userdata8",SimTextFieldUserdata8},
	{"userdata9",SimTextFieldUserdata9},
};

int sim_text_field_get_index (const char *s){
	int res = -1;
	int i;
	for (i = 0;i < (sizeof(simtextfields)/sizeof (simtextfields[0])) && res == -1;i++){
		if (strcmp (s,simtextfields[i].name) == 0) res = simtextfields[i].index;
	}
	return res;
}
const gchar *sim_text_field_get_name (guint inx){
	assert (inx < N_TEXT_FIELDS);
	return simtextfields[inx].name;
}
