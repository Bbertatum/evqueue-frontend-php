$(document).delegate('.showWorkflowDetails','click',function() {
	var wfid = $(this).data('id');
	var node = $(this).data('node-name');
	var status = $(this).data('status');
	
	var container = $('<div>');
	container.attr('data-url',"ajax/instance.php?id="+wfid+"&node="+node);
	container.attr('data-interval',status=='TERMINATED'?0:5);
	container.addClass('evq-autorefresh');
	$('#workflow-dialogs').append(container);
	
	var dialog = $('#workflow-dialog').clone();
	dialog.find('ul').append('<li><a href="#workflow-'+wfid+'">Tree</a></li>')
	dialog.find('ul').append('<li><a href="#workflow-'+wfid+'-xml">XML</a></li>')
	dialog.find('ul').append('<li><a href="#workflow-'+wfid+'-parameters">Parameters</a></li>')
	
	
	dialog.append('<div class="evq-autorefresh-pannel" id="workflow-'+wfid+'"></div>');
	dialog.append('<div class="evq-autorefresh-pannel" id="workflow-'+wfid+'-xml"></div>');
	dialog.append('<div class="evq-autorefresh-pannel" id="workflow-'+wfid+'-parameters"></div>');
	dialog.tabs();
	dialog.dialog({
		width:'auto',
		height:'auto',
		appendTo:container,
		title:'Instance '+wfid,
		close:function() { container.evqautorefresh('disable'); container.remove(); }
	});
	
	container.evqautorefresh();
	
	dialog.delegate('.taskName','click',function() {
		TaskDialog(container,wfid,$(this).parent().data('evqid'),$(this).parent().data('name'),1);
	});
});

function TaskDialog(container,wfid,evqid,name,idx)
{
	var dialog = $('#task-dialog').clone();
	dialog.find('ul').append('<li><a href="#'+wfid+'-'+evqid+'-general">General</a></li>')
	dialog.find('ul').append('<li><a href="#'+wfid+'-'+evqid+'-stdout-'+idx+'">stdout</a></li>')
	dialog.find('ul').append('<li><a href="#'+wfid+'-'+evqid+'-stderr-'+idx+'">stderr</a></li>')
	dialog.find('ul').append('<li><a href="#'+wfid+'-'+evqid+'-log-'+idx+'">log</a></li>')
	if(idx==1)
		dialog.find('ul').append('<li><a href="#'+wfid+'-'+evqid+'-executions">Previous executions</a></li>')
	
	dialog.append('<div class="evq-autorefresh-pannel" id="'+wfid+'-'+evqid+'-general"></div>')
	dialog.append('<div class="evq-autorefresh-pannel" id="'+wfid+'-'+evqid+'-stdout-'+idx+'"</div>')
	dialog.append('<div class="evq-autorefresh-pannel" id="'+wfid+'-'+evqid+'-stderr-'+idx+'"</div>')
	dialog.append('<div class="evq-autorefresh-pannel" id="'+wfid+'-'+evqid+'-log-'+idx+'"</div>')
	if(idx==1)
		dialog.append('<div class="evq-autorefresh-pannel" id="'+wfid+'-'+evqid+'-executions"</div>')
	dialog.tabs();
	dialog.dialog({
		width:600,
		title:'Task '+name,
		appendTo:container,
		close:function() { $(this).dialog('destroy'); }
	});
	
	container.evqautorefresh('refresh');
	
	dialog.delegate('.task_execution','click',function() {
		TaskDialog(container,wfid,evqid,$(this).index()+1);
	});
}

function CancelInstance(id,node,killtasks = false)
{
	evqueueAPI({
		group: 'instance',
		action: 'query',
		attributes: { 'id':id }
	}).done( function (xml) {
		var subjobs = xml.Query('subjobs',xml.documentElement.firstChild);
		
		evqueueAPI({
			group: 'instance',
			action: 'cancel',
			attributes: { 'id':id },
			node: node
		}).done(function() {
			Message('Canceled instance '+id);
			if(killtasks)
				KillRunningTasks(subjobs[0],id,node);
		});
	});
}

function KillRunningTasks(subjobs,id,node)
{
	var xmldoc = subjobs.ownerDocument;
	jobs = xmldoc.Query('job',subjobs);
	for(var i=0;i<jobs.length;i++)
	{
		var tasks = xmldoc.Query("tasks/task[@status = 'EXECUTING']",jobs[i]);
		for(var j=0;j<tasks.length;j++)
		{
			var task_name = tasks[j].getAttribute('name');
			evqueueAPI({
				group: 'instance',
				action: 'killtask',
				attributes: { 'id':id, 'pid':tasks[j].getAttribute('pid') },
				node: node
			}).done(function() {
				Message('Killed task '+task_name);
			});
		
			var job_subjobs = xmldoc.Query('subjobs',jobs[i]);
			if(job_subjobs.length>0)
				KillRunningTasks(job_subjobs[0],id,node);
		}
	}
}