<div class="areaimage" >
	<center>
		<img src="{TPL_images}companies-icon_2.png" height="72px"/>
	</cente>
</div>

<script type="text/javascript"> 
    $(document).ready(function(){		
        window.document.body.oncopy  = function() { return false; };
        window.document.body.onpaste = function() { return false; }
    });
</script> 
	
<div id="msg" style="display:none;"><img src="{TPL_images}loader.gif" alt="Enviando" />Aguarde carregando...</div> 

<div id="view_content">	

    <?php
    echo $link_back;
    echo $message;
    ?>
	
	<div class="formulario">	
	
	
	<form class="form-horizontal" role="form" id="frm1" name="frm1" action="<?php echo $form_action; ?>" method="post">
		
		
	<div class="panel panel-info">

		  <div class="panel-heading">
		    <h3 class="panel-title"><?php echo $titulo; ?></h3>
		  </div>
		  
		  
		  <div class="panel-body">
		  
		  	
				  <div class="form-group <?php echo (form_error('campoNome') != '')? 'has-error':''; ?>"">
				    <label for="campoNome" class="col-sm-3 control-label">Nome</label>
				    <div class="col-md-6">
				      	<?php echo form_input($campoNome); ?> 
				     </div>
				  </div>
				  
				  
				  <div class="form-group <?php echo (form_error('campoSigla') != '')? 'has-error':''; ?>">
				    <label for="campoSigla" class="col-sm-3 control-label">Sigla</label>
				    <div class="col-md-6">
				   	 	<?php echo form_input($campoSigla); ?>
				    </div>
				  </div>
				  
				  
				  <div class="form-group <?php echo (form_error('campoEndereco') != '')? 'has-error':''; ?>">
				    <label for="campoEndereco" class="col-sm-3 control-label">Endereço</label>
				    <div class="col-md-6">
				    	<?php echo form_textarea($campoEndereco); ?>
				    </div>
				  </div>
				  
				  
				    <?php 
				    if(validation_errors() != ''){
							echo '<div class="form-group">';
							echo form_error('campoNome');
							echo form_error('campoSigla'); 
							echo form_error('campoEndereco'); 
							echo '</div>';
							}
					?>
				  

			
		    
		  </div>
	</div>
	
	
	<div class="btn-group">
	   		<?php
		    	echo $link_cancelar;
		    	echo $link_salvar;
		    ?>
	</div>
	
	</form>
						
    	
    </div>

</form> 

</div><!-- fim: div view_content --> 
