//this function use for checkall check boxes
function delete_videos()
{
	var frm_obj = document.video_list;
		if(check_chebox_checked(frm_obj["delId[]"]))
		{
			var result = confirm("Are you sure! you want to delete Videos");
			if(result==true)
			{
				frm_obj.action="options-general.php?page=wp_post_video_player&action=deleteAll";
				frm_obj.submit();

			}
		}
		else
		{
			alert("You have not selected any Post Details");
			return false;
		}
}
function check_chebox_checked(checkbox)
{
	var count =0;
	if(checkbox.length == undefined)
	{
		if(checkbox.checked==true)
			count = 1;
	}
	else
	{
		for(i=0;i<checkbox.length;i++)
		{
			if(checkbox[i].checked)
			{
				count++;
			}
		}
	}
	if(count==0)
		return false;
	else
		return true;
}

function check_all()
{
	var frm_obj = document.post_details;
	if(frm_obj.post_details.checked == true)
	{
		check_all_checkboxes(frm_obj['delId[]'],true);
	}
	else
	{
		check_all_checkboxes(frm_obj['delId[]'],false);
	}
}
function check_all_checkboxes(checkbox,option)
{
	if(checkbox.length == undefined)
	{
		checkbox.checked=option;
	}
	else
	{
		for(i=0;i<checkbox.length;i++)
			checkbox[i].checked = option;
	}
}