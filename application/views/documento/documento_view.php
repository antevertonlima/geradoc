<link rel="stylesheet" href="<?php echo base_url(); ?>css/pdf.css">

<div class="areaimage">
	<center>
		<img src="{TPL_images}Actions-document-edit-icon.png" height="72px" />
	</center>
</div>

<p class="bg-success lead text-center">Documento</p>

<div id="msg" style="display: none;">
	<img src="{TPL_images}loader.gif" alt="Enviando" />Aguarde
	carregando...
</div>

<div id="view_content">

	<div class="row">
    
	    <div class="col-md-12 text-center">
	    	<div class="btn-group">
		    <?php

		    echo $link_back;
		    echo $link_export_sm;
		    echo $link_update_sm;
		    echo $link_stamp;
		    echo $link_history;
		    echo $link_workflow;
		   
		    ?>
		  	</div>  
	    </div>

    </div>


	<div class="formulario">
	
	
	<div class="pagina">
	
	
	
	<?php 
	
	$header = '<div style="padding-bottom: 20px;">
				<table width="100%" style="vertical-align: bottom;">
				<tr>
				<td align="center">'.$cabecalho.'</td>
				</tr>
				</table>
				</div>';
	
	if($objeto->carimbo == 'S'){
		$header .= '<div style="position: absolute; float: right; text-align: right; margin-top:-119px; margin-left: 617px; font-size: 10pt; color: #555; line-height:200%;">
						<img src="../../../images/carimbo_aesp.png" width="80px"/>
					</div>';
	}
	
	
	$content = '<div class="conteudo" style="min-height:900px; font-size:17px;">
				'.htmlspecialchars_decode($objeto->layout).'
			</div>';
	
	$footer = '
		<table width="100%" style="vertical-align: top;font-family:\'Times New Roman\',Times,serif; font-size: 11px;">
			<tr>
				<td align="center" colspan="2">
					<div style="padding-top: 20px;">'.$rodape.'</div>
				</td>
			</tr>
			<tr>
				<td style="font-size: 9px" align="left">'.$documento_identificacao.'</td>	
				<td align="right">página x de x</td>
			</tr>
		</table>
		';
	
	echo $header;
	echo $content;
	echo $footer;
	
	?>
	
	</div>	

	</div>
	<!-- fim da div formulario -->
	
	<div class="row" style="padding-top: 15px">
    
	    <div class="col-md-12 text-center">
	    	<div class="btn-group">
		    <?php

		    echo $link_back;
		    echo $link_export_sm;
		    echo $link_update_sm;
		    echo $link_stamp;
		    echo $link_history;
		    echo $link_workflow;
		   
		    ?>
		  	</div>  
	    </div>

    </div>
	
</div>
<!-- fim da div  view_content -->
