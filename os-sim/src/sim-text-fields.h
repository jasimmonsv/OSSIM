#ifndef __SIM_TEXT_FIELDS_H__
#define __SIM_TEXT_FIELDS_H__ 1
#define N_TEXT_FIELDS 12
enum SimTextIndexFields{
	SimTextFieldNone  = -1,
	SimTextFieldUsername = 0,
	SimTextFieldPassword = 1,
	SimTextFieldFilename = 2,
	SimTextFieldUserdata1 = 3,
	SimTextFieldUserdata2 = 4,
	SimTextFieldUserdata3 = 5,
	SimTextFieldUserdata4 = 6,
	SimTextFieldUserdata5 = 7,
	SimTextFieldUserdata6 = 8,
	SimTextFieldUserdata7 = 9,
	SimTextFieldUserdata8 = 10,
	SimTextFieldUserdata9 = 11
};

int sim_text_field_get_index (const char *);	
const gchar * sim_text_field_get_name (guint inx);
const gchar *sim_text_field_get_var_name (guint inx);
int sim_text_field_get_var_index (const char *name);
#endif
