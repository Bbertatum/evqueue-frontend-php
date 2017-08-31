$(document).ready( function() {
	var menu_selected=$('ul.topmenu li.selected').attr('id');
	$('ul.topmenu li').mouseenter(function(event) {
			$('ul.submenu').hide();
			$('ul.submenu#submenu-'+event.target.id).show();
		}
	);
	
	$('.tabs').tabs();
	
	$('.spinner').spinner();
	
	$('.evq-autorefresh').evqautorefresh();
});

XMLDocument.prototype.Query = function(xpath, context)
{
	var results = this.evaluate(xpath,context,null,XPathResult.ANY_TYPE, null);
	var ret = [];
	while (result = results.iterateNext())
		ret.push(result);
	return ret;
}

function evqueueAPI(options){
	options = $.extend({confirm: '', attributes: [], parameters: []}, options);
	
	var promise = new jQuery.Deferred();
	
	if (options.confirm && !confirm(options.confirm))
		return promise.reject();
	
	$.ajax({
		url: 'ajax/evqueue_api.php',
		type: 'post',
		data: {
			group: options.group,
			action: options.action,
			attributes: options.attributes,
			parameters: options.parameters,
			node: options.node,
		},
	}).done(function(xml){
		error = $(xml).find('error');
		if ($(error).length > 0) {
			if(!options.ignore_errors)
				alert(error.html());
			promise.reject();
			return;
		}
		
		promise.resolve(xml);
	});
	
	return promise;
}

function Wait()
{
	$('html').css('height','calc(100% - 50px)');
	$('body').css('height','calc(100% - 50px)');
	$('body').css("cursor", "wait");
}

function Ready()
{
	$('html').css('height','auto');
	$('body').css('height','auto');
	$('body').css("cursor", "default");
}

function Message(msg)
{
	$('#message').html(msg);
	$('#message').show();
	$('#message').delay(2000).fadeOut();
}

var dialog_currpos = 0;
var dialog_positions = ['left top', 'right top', 'left bottom', 'right bottom'];
jQuery.fn.extend({
	dialogTiled: function(options) {
	if(!options)
		options = {};
	
	return this.each(function() {
			var pos = dialog_positions[dialog_currpos];
			dialog_currpos = (dialog_currpos + 1) % 4;
			
			options.position = {
				my: pos,
				at: pos,
				of: window
			};
      $(this).dialog(options);
    });
  }
});

jQuery.fn.extend({
	evqautorefresh: function(action=false) {
		this.each(function() {
			var el = $(this);
			
			if(action===false)
			{
				var toggle = el.find('span.evq-autorefresh-toggle');
				if(toggle)
				{
					if(el.data('interval')>0)
					{
						toggle.addClass('fa-spin');
						toggle.attr('title','Auto-refresh is enabled');
					}
					
					toggle.click(function() {
						if(el.data('interval')==0)
							autorefresh(el,toggle);
						else
						{
							$(this).toggleClass('fa-spin');
							
							if($(this).hasClass('fa-spin'))
							{
								autorefresh(el,toggle);
								$(this).attr('title','Auto-refresh is enabled');
								Message('Auto refresh enabled');
							}
							else
							{
								$(this).attr('title','Auto-refresh is disabled');
								clearTimeout(el.data('timeout'));
								Message('Auto refresh stopped');
							}
						}
					});
				}
				
				Wait();
				autorefresh(el,toggle).done(function() {
					Ready();
				});
			}
			else if(action=='disable')
				clearTimeout(el.data('timeout'));
			else if(action=='refresh')
				autorefresh(el,false);
		});
	}
});


function autorefresh(el,toggle)
{
	var interval = el.data('interval')?el.data('interval'):0;
	var url = el.data('url');
	var pannels = el.find('div.evq-autorefresh-pannel');
	
	return $.ajax({url: url}).done(function(data) {
		if(pannels.length==1)
			$(pannels[0]).html(data);
		else
		{
			for(var i=0;i<pannels.length;i++)
			{
				var pannel_id = $(pannels[i]).attr('id');
				$(pannels[i]).html($(data).find('#'+pannel_id).html());
			}
		}
		
		if(interval>0 && toggle!==false && (toggle.length==0 || toggle.hasClass('fa-spin')))
		{
			var timeout = setTimeout(function() { autorefresh(el,toggle); } ,interval*1000);
			el.data('timeout',timeout);
		}
	});
}
