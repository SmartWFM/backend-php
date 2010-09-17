function addNotice(text) {
	$('#notify').append('<div class="notice">'+text+'</div>');
}

function addError(text) {
	$('#notify').append('<div class="error">'+text+'</div>');
}
		
function setCorrectFlag(id){
	$('#'+id).attr('src', 'images/correct.png');
}
		
function setFalseFlag(id){
	$('#'+id).attr('src', 'images/false.png');
}
		
function checkConfigDir() {
	$.ajax({
		url: 'check.php',
		data: {'check': 1},
		success: function(data) {
			if(data.error == false){
				if(data.result.writable)
					addNotice('Directory is writable');
				else
					addError('Directory is not writable');
				if(data.result.overwrite)
					addError('Config file exists');
				else
					addNotice('Config file doesn\'t exists');
				// load data
				$('input[name="basepath"]').val(data.result.config.base_path);
				$('input[name="commands_path"]').val(
					data.result.config.commands_path);
				checkBasePath();
				checkCommandsPath();
			}
		}
	});
}
		
function init() {
	checkConfigDir();
	$('#loading').hide();
	$('#settingsform').submit(function() {
		$.ajax({
			url: 'libinstall.php',
			data: $('#settingsform').serializeArray(),
			success: function(data) {
				$('#result').html(data);
				/*if(data.error == false){
					if(data.result.correct) {
						setCorrectFlag('commands_path-check');
						loadCommands();
					} else {
						setFalseFlag('commands_path-check');
						$('#commands').html('');						
					}				
				} else {
					setFalseFlag('commands_path-check');
					$('#commands').html('');	
				}*/
			}
		});
		return false;
	});
}
		
function checkBasePath() {
	$.ajax({
		url: 'check.php',
		data: {
			'check': 2, 
			'path': $('input[name="basepath"]').val()
		},
		success: function(data) {
			if(data.error == false){
				if(data.result.correct)
					setCorrectFlag('basepath-check');
				else
					setFalseFlag('basepath-check');		
			}
			else
				setFalseFlag('basepath-check');
		}
	});
}
		
function checkCommandsPath() {
	$.ajax({
		url: 'check.php',
		data: {'check': 3, 'path': $('input[name="commands_path"]').val()},
		success: function(data) {
			if(data.error == false){
				if(data.result.correct) {
					setCorrectFlag('commands_path-check');
					loadCommands();
				} else {
					setFalseFlag('commands_path-check');
					$('#commands').html('');						
				}				
			} else {
				setFalseFlag('commands_path-check');
				$('#commands').html('');	
			}
		}
	});
}
		
function loadCommands() {
	$.ajax({
		url: 'check.php',
		data: {
			'check': 4, 
			'path': $('input[name="commands_path"]').val()
		},
		success: function(data) {
			if(data.error == false){
				$('#commands').html(
					'<img id="setting_filename-check" src="images/false.png"/>'+
					' <label for=\"commands\">commands</label><br/>');
				for(i in data.result) {
					$('#commands').append(
						'<input name="commands[]" type="checkbox" value="'+
						data.result[i]+'" />'+data.result[i]+'<br />'
					);
				}				
			} else
				setFalseFlag('commands_path-check');
		}
	});
}
