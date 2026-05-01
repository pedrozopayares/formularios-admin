<form role="form" id="nuevoregistro" name="nuevoregistro" action="registrarautodecalaracionliquidos.php" method="post" enctype="multipart/form-data">
            <div class="row">
            	<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <h2 class="page-header">
                        <i class="fa fa-pencil-square-o" aria-hidden="true"></i> FORMULARIO DE AUTODECLARACIÓN DE REGISTROS MENSUALES DE VOLUMENES DE AGUAS CAPTADAS Y RETORNADAS A LA FUENTE
                    </h2>
                    <p>Señor Usuario antes de diligenciar el Formulario, favor leer el Instructivo adjunto. Una vez diligenciado debe ser enviado a la C.R.A., con los anexos pertinente para ser radicado en ventanilla unica. Recuerde que la fecha límite para este tramite es el último día habil del primer mes del año.  </p>
                </div>
            </div>
            <div class="panel panel-default">
				<div class="panel-heading">
				    <h3 class="panel-title">1. INFORMACIÓN GENERAL DEL USUARIO </h3>
				</div>
				<div class="panel-body">
					<div class="row">
		            	<div class="form-group col-xs-12 col-sm-12 col-md-6 col-lg-6">
						    <label for="nombre_o_razon_social" class="control-label">Nombre o razón social <span class="text-danger">*</span></label><br>
						    <input type="text" class="form-control" id="nombre_o_razon_social" name="nombre_o_razon_social" required="" placeholder="Nombre o razon social">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-6 col-lg-6">
						    <label for="anoderepote" class="control-label"> Año de Reporte de Autodeclaración: <span class="text-danger">*</span></label><br>
						    <input type="text" class="form-control" id="anoderepote" name="anoderepote" required="" placeholder="Año de Reporte de Autodeclaración">
						</div>
					</div>
					<div class="row">
						<div class="form-group col-xs-12 col-sm-12 col-md-6 col-lg-6">
						    <label for="numero_de_identificacion_o_nit" class="control-label">No de Identificación (C.C. o Nit) <span class="text-danger">*</span></label><br>
						    <input type="text" class="form-control" id="numero_de_identificacion_o_nit" name="numero_de_identificacion_o_nit" required="" placeholder="Número de identificación o NIT">
						</div>
		            	<div class="form-group col-xs-12 col-sm-12 col-md-6 col-lg-6">
						    <label for="dvdigitoverf" class="control-label">DV (Digito de Verificación) <span class="text-danger">*</span></label><br>
						    <input type="text" class="form-control" id="dvdigitoverf" name="dvdigitoverf" required="" placeholder="DV (Digito de Verificación)">
						</div>						
					</div>
					<div class="row">
						<div class="form-group col-xs-12 col-sm-12 col-md-6 col-lg-6">
						    <label for="representante_legal" class="control-label">Representante legal <span class="text-danger">*</span></label><br>
						    <input type="text" class="form-control" id="representante_legal" name="representante_legal" required="" placeholder="Representante legal">
						</div>
		            	<div class="form-group col-xs-12 col-sm-12 col-md-6 col-lg-6">
						    <label for="numero_de_id_rp_legal" class="control-label">Número de Identificación de Representante Legal:  <span class="text-danger">*</span></label><br>
						    <input type="text" class="form-control" id="numero_de_id_rp_legal" name="numero_de_id_rp_legal" required="" placeholder="Número de Identificación de Representante Legal">
						</div>			
					</div>
					<div class="row">
						<div class="form-group col-xs-12 col-sm-12 col-md-6 col-lg-6">
						    <label for="direccion" class="control-label">Dirección de Correspondencia <span class="text-danger">*</span></label><br>
						    <input type="text" class="form-control" id="direccion" name="direccion" required="" placeholder="Dirección de Correspondencia">
						</div>
		            	<div class="form-group col-xs-12 col-sm-12 col-md-6 col-lg-6">
						    <label for="municipio_o_corregimiento" class="control-label">Municipio y/o Corregimiento <span class="text-danger">*</span></label><br>
						    <input type="text" class="form-control" id="municipio_o_corregimiento" name="municipio_o_corregimiento" required="" placeholder="Municipio y/o Corregimiento">
						</div>	
					</div>
					<div class="row">
						<div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4">
						    <label for="numero_de_contacto" class="control-label">Numero de contacto: <span class="text-danger">*</span></label><br>
						    <input type="text" class="form-control" id="numero_de_contacto" name="numero_de_contacto" required="" placeholder="Teléfono y/o No. de Celular de contacto:">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4">
						    <label for="fax" class="control-label">Fax: </label><br>
						    <input type="text" class="form-control" id="fax" name="fax" placeholder="Fax">
						</div>
		            	<div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4">
						    <label for="correo" class="control-label">E-mail: <span class="text-danger">*</span></label><br>
						    <input type="email" class="form-control" id="correo" name="correo" required="" placeholder="Correo">
						</div>
					</div>
				</div>
			</div>
			<div class="panel panel-default">
				<div class="panel-heading">
				    <h3 class="panel-title">2. INFORMACIÓN DE LA EMPRESA </h3>
				</div>
				<div class="panel-body">
					<div class="row">
		            	<div class="form-group col-xs-12 col-sm-12 col-md-3 col-lg-3">
						    <label for="tipo_industria" class="control-label">Tipo de Industria: <span class="text-danger">*</span></label><br>
						    <input type="text" class="form-control" id="tipo_industria" name="tipo_industria" required="" placeholder="Tipo de Industria:">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-3 col-lg-3">
						    <label for="codigo_ciiu" class="control-label">Código CIIU: <span class="text-danger">*</span></label><br>
						    <input type="text" class="form-control" id="codigo_ciiu" name="codigo_ciiu" required="" placeholder="Código CIIU:">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-3 col-lg-3">
						    <label for="numero_turnos_dia" class="control-label"> No. de turnos/día: <span class="text-danger">*</span></label><br>
						    <input type="text" class="form-control" id="numero_turnos_dia" name="numero_turnos_dia" required="" placeholder=" No. de turnos/día:">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-3 col-lg-3">
						    <label for="numero_emplados_turnos" class="control-label">No.de Empleados/turno: <span class="text-danger">*</span></label><br>
						    <input type="text" class="form-control" id="numero_emplados_turnos" name="numero_emplados_turnos" required="" placeholder="No.de Empleados/turno:">
						</div>						
					</div>
				</div>
			</div>


			<div class="panel panel-default">
				<div class="panel-heading">
				    <h3 class="panel-title">3. TIPO DE USO DEL AGUA </h3>
				</div>
				<div class="panel-body">
					<div class="row">
						<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12">
		                    <div class="form-group">								                     
		                        
		                        <label class="radio-inline"><input type="radio" name="tipo_de_usu_de_agua" id="tipo_de_usu_de_agua" value="Domestico">Domestico</label>&nbsp;&nbsp;&nbsp;
		                        <label class="radio-inline"><input type="radio" name="tipo_de_usu_de_agua" id="tipo_de_usu_de_agua" value="Industrial">Industrial</label>&nbsp;&nbsp;&nbsp;	
		                        <label class="radio-inline"><input type="radio" name="tipo_de_usu_de_agua" id="tipo_de_usu_de_agua" value="Agricola">Agricola</label>&nbsp;&nbsp;&nbsp;	
		                        <label class="radio-inline"><input type="radio" name="tipo_de_usu_de_agua" id="tipo_de_usu_de_agua" value="Pecuario">Pecuario</label>&nbsp;&nbsp;&nbsp;	
		                        <label class="radio-inline"><input type="radio" name="tipo_de_usu_de_agua" id="tipo_de_usu_de_agua" value="Piscicola">Piscicola</label>&nbsp;&nbsp;&nbsp;	
		                        <label class="radio-inline"><input type="radio" name="tipo_de_usu_de_agua" id="tipo_de_usu_de_agua" value="Recreativo">Recreativo</label>&nbsp;&nbsp;&nbsp;	
		                        <label class="radio-inline"><input type="radio" name="tipo_de_usu_de_agua" id="tipo_de_usu_de_agua" value="Estético">Estético</label>&nbsp;&nbsp;&nbsp;	
		                    </div>
		                </div>
		            </div>
		            <div class="row">
		            	<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12">
						    <label for="tipo_de_usu_de_agua_otro" class="control-label">Otro,  Cual?: </label><br>
						    <input type="text" class="form-control" id="tipo_de_usu_de_agua_otro" name="tipo_de_usu_de_agua_otro" placeholder="Otro tipo de uso">
						</div>
					</div>						
				</div>
			</div>
					
											

			<div class="panel panel-default">
				<div class="panel-heading">
				    <h3 class="panel-title">4.  DATOS DEL PERMISO DE CONCESIÓN DE AGUA Y FUENTE DE ABASTECIMIENTO </h3>
				</div>

						 											

				<div class="panel-body">
					<div class="row">
						<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12">
		                    <div class="form-group">								                     
		                        <label for="tipo_de_fuente_de_captacion" class="control-label">Tipo de fuente de captación: </label><br>
		                        <label class="radio-inline"><input type="radio" name="tipo_de_fuente_de_captacion" id="tipo_de_fuente_de_captacion" value="Rio">Rio</label>&nbsp;&nbsp;&nbsp;
		                        <label class="radio-inline"><input type="radio" name="tipo_de_fuente_de_captacion" id="tipo_de_fuente_de_captacion" value="Quebrada">Quebrada</label>&nbsp;&nbsp;&nbsp;
		                        <label class="radio-inline"><input type="radio" name="tipo_de_fuente_de_captacion" id="tipo_de_fuente_de_captacion" value="Laguna">Laguna</label>&nbsp;&nbsp;&nbsp;
		                        <label class="radio-inline"><input type="radio" name="tipo_de_fuente_de_captacion" id="tipo_de_fuente_de_captacion" value="Nacimiento">Nacimiento</label>&nbsp;&nbsp;&nbsp;
		                        <label class="radio-inline"><input type="radio" name="tipo_de_fuente_de_captacion" id="tipo_de_fuente_de_captacion" value="Pozo Sub">Pozo Sub</label>&nbsp;&nbsp;&nbsp;
		                        <label class="radio-inline"><input type="radio" name="tipo_de_fuente_de_captacion" id="tipo_de_fuente_de_captacion" value="Reuso">Reuso</label>&nbsp;&nbsp;&nbsp;		                        
		                    </div>
		                </div>
		            </div>
		            <div class="row">
		            	<div class="form-group col-xs-12 col-sm-12 col-md-6 col-lg-6">
						    <label for="tipo_de_fuente_de_captacion_otro" class="control-label">Otro,  Cual?: </label><br>
						    <input type="text" class="form-control" id="tipo_de_fuente_de_captacion_otro" name="tipo_de_fuente_de_captacion_otro" placeholder="Otro cual?">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-6 col-lg-6">
							<label for="vereda_donde_realiza_la_captacion" class="control-label">Vereda donde realiza la captación: </label><br>
						    <input type="text" class="form-control" id="vereda_donde_realiza_la_captacion" name="vereda_donde_realiza_la_captacion" placeholder="Vereda donde realiza la captación">
						</div>
					</div>	
		          	<div class="row">
		            	<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12">
						    <h3>Objetivo de Calidad:</h3>
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4">
							<label for="dbo5_mg_l" class="control-label">DBO5 (mg/L) </label><br>
						    <input type="text" class="form-control" id="dbo5_mg_l" name="dbo5_mg_l" placeholder="DBO5 (mg/L)">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4">
							<label for="sst_md_l" class="control-label">SST(mg/L) </label><br>
						    <input type="text" class="form-control" id="sst_md_l" name="sst_md_l" placeholder="SST(mg/L)">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4">
							<label for="empresa_reliza_medic_resol_ideam" class="control-label">Empresa que reliza la medición/Resolución IDEAM: </label><br>
						    <input type="text" class="form-control" id="empresa_reliza_medic_resol_ideam" name="empresa_reliza_medic_resol_ideam" placeholder="Empresa que reliza la medición/Resolución IDEAM:">
						</div>
					</div>
					<div class="row">
						<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12">						    
		            		<label for="coordenadas" class="control-label"><br>Coordenadas del punto de captación (Planas Magna Sirgas Origen Nacional): </label>
		            	</div>
		            </div>
            		<div class="row">
            			<div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4">
					      	<input type="text" class="form-control" id="coordenadasx" name="coordenadasx" placeholder="Coordenadas X:">
					  	</div>
					  	<div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4">
					      	<input type="text" class="form-control" id="coordenadasy" name="coordenadasy" placeholder="Coordenadas Y:">
					  	</div>
					  	<div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4">
					      	<input type="text" class="form-control" id="altura_msnm" name="altura_msnm" placeholder="Altura (msnm):">
					  	</div>
					 </div>
							
								 			 				



					<div class="row">
						<div class="form-group col-xs-12 col-sm-12 col-md-3 col-lg-3">
						    <label for="caudal_otorgado_ls" class="control-label"> Caudal Otorgado (l/s): </label><br>	
						    <input type="text" class="form-control" id="caudal_otorgado_ls" name="caudal_otorgado_ls" placeholder=" Caudal Otorgado (l/s)">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-3 col-lg-3">
						    <label for="no_resolucion" class="control-label">No. Resolución: </label><br>	
						    <input type="text" class="form-control" id="no_resolucion" name="no_resolucion" placeholder="No. Resolución:">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-2 col-lg-2">
						    <label for="fecha_publicacion" class="control-label"> Fecha de publicación: </label><br>	
						    <input type="date" class="form-control" id="fecha_publicacion" name="fecha_publicacion">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-2 col-lg-2">
						    <label for="fecha_vencimiento" class="control-label"> Fecha Vencimiento: </label><br>	
						    <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-2 col-lg-2">
						    <label for="no_expediente" class="control-label">No. Expediente: </label><br>	
						    <input type="text" class="form-control" id="no_expediente" name="no_expediente" placeholder="No. Expediente:">
						</div>
					</div>
					<div class="row">						 	
						<div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4">
						    <label for="cuenca_de_la_fuente" class="control-label"> Cuenca de la fuente: </label><br>	
						    <input type="text" class="form-control" id="cuenca_de_la_fuente" name="cuenca_de_la_fuente" placeholder=" Cuenca de la fuente:">
						</div>								
						<div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4">
						    <label for="subcuenca_de_la_fuente" class="control-label"> Subcuenca de la fuente: </label><br>	
						    <input type="text" class="form-control" id="subcuenca_de_la_fuente" name="subcuenca_de_la_fuente" placeholder=" Subcuenca de la fuente:">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4">
		                    <div class="form-group">								                     
		                        <label for="cuenta_con_mas_puntos_de_captacion_en_la_misma_concesion">Cuenta con mas puntos de captación  en la misma concesión(SI/ NO): </label><br>	
		                        <label class="radio-inline"><input type="radio" name="cuenta_con_mas_puntos_de_captacion_en_la_misma_concesion" id="cuenta_con_mas_puntos_de_captacion_en_la_misma_concesion" value="SI">SI</label>&nbsp;&nbsp;&nbsp;
								<label class="radio-inline"><input type="radio" name="cuenta_con_mas_puntos_de_captacion_en_la_misma_concesion" id="cuenta_con_mas_puntos_de_captacion_en_la_misma_concesion" value="NO">NO</label>	
		                    </div>
		                </div>						
					</div>
				</div>
			</div>
			<div class="panel panel-default">
				<div class="panel-heading">
				    <h3 class="panel-title">5.  DATOS DEL SISTEMA DE MEDICION DE AGUA CAPTADA </h3>
				</div>
				<div class="panel-body">
					<div class="row">
		            	<div class="form-group col-xs-12 col-sm-12 col-md-6 col-lg-6">
						    <label for="tipo_marca_sis_medicion" class="control-label">Tipo  y Marca del sistema de medición </label><br>
						    <input type="text" class="form-control" id="tipo_marca_sis_medicion" name="tipo_marca_sis_medicion" placeholder="Tipo y Marca del sistema de medición">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-2 col-lg-2">
						    <label for="modelo" class="control-label">Modelo: </label><br>
						    <input type="text" class="form-control" id="modelo" name="modelo" placeholder="Modelo:">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-2 col-lg-2">
						    <label for="serie" class="control-label">Série: </label><br>
						    <input type="text" class="form-control" id="serie" name="serie" placeholder="Série">
						</div>		
						<div class="form-group col-xs-12 col-sm-12 col-md-2 col-lg-2">
						    <label for="fecha_instalacion" class="control-label">Fecha de Instalación: </label><br>
						    <input type="date" class="form-control" id="fecha_instalacion" name="fecha_instalacion" placeholder="Fecha de Instalación">
						</div>						
					</div>
					<div class="row">
		            	<div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4">
						    <label for="fecha_de_calibracion" class="control-label">Fecha de calibración: </label><br>
						    <input type="date" class="form-control" id="fecha_de_calibracion" name="fecha_de_calibracion" placeholder="Fecha de calibración">
						</div>
						
						<div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4">
						    <label for="empresa_que_realiza_la_calibracion" class="control-label">Empresa que realiza la Calibración:	 </label><br>
						    <input type="text" class="form-control" id="empresa_que_realiza_la_calibracion" name="empresa_que_realiza_la_calibracion" placeholder="Empresa que realiza la Calibración:">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4">
		                    <div class="form-group">								                     
		                        <label for="vigencia_superior_dos_anos">Vigencia superior a dos años: </label><br>		
		                        <label class="radio-inline"><input type="radio" name="vigencia_superior_dos_anos" id="vigencia_superior_dos_anos" value="SI">SI</label>&nbsp;&nbsp;&nbsp;
								<label class="radio-inline"><input type="radio" name="vigencia_superior_dos_anos" id="vigencia_superior_dos_anos" value="NO">NO</label>	
		                    </div>
		                </div>
					</div>
				</div>					

			</div>
			<div class="panel panel-default">
				<div class="panel-heading">
				    <h3 class="panel-title">6. REPORTE MENSUAL VOLÚMENES DE AGUA CAPTADA </h3>
				</div>
				<div class="panel-body">
					<div class="row">
						<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12">
							<table class="table table-striped">
								<thead>
								    <tr>
								      	<th scope="col">MES</th>
								      	<th scope="col">Enero</th>
								      	<th scope="col">Febrero</th>
								      	<th scope="col">Marzo</th>
								      	<th scope="col">Abril</th>
								      	<th scope="col">Mayo</th>
								      	<th scope="col">Junio</th>
								  	</tr>
								</thead>
								<tbody>
								    <tr>
								      	<th scope="row" width="400"><span style="font-size :10px;">FECHA DE LECTURA</span></th>
								      	<td><input type="date" class="form-control" id="enero1" name="enero1"></td>
								      	<td><input type="date" class="form-control" id="febrero1" name="febrero1"></td>
								      	<td><input type="date" class="form-control" id="marzo1" name="marzo1"></td>
								      	<td><input type="date" class="form-control" id="abril1" name="abril1"></td>
								      	<td><input type="date" class="form-control" id="mayo1" name="mayo1"></td>
								      	<td><input type="date" class="form-control" id="junio1" name="junio1"></td>
								  	</tr>
								    <tr>
								      	<th scope="row"><span style="font-size :10px;">PERIODO DE CLIMA <br>(Lluvia/ Sequia)</span></th>
								      	<td><input type="text" class="form-control" id="enero2" name="enero2"></td>
								      	<td><input type="text" class="form-control" id="febrero2" name="febrero2"></td>
								      	<td><input type="text" class="form-control" id="marzo2" name="marzo2"></td>
								      	<td><input type="text" class="form-control" id="abril2" name="abril2"></td>
								      	<td><input type="text" class="form-control" id="mayo2" name="mayo2"></td>
								      	<td><input type="text" class="form-control" id="junio2" name="junio2"></td>
								  	</tr>
								    <tr>
								      	<th scope="row"><span style="font-size :10px;">PERIODO DE USO <br>(días / mes)</span> </th>
								      	<td><input type="text" class="form-control" id="enero3" name="enero3"></td>
								      	<td><input type="text" class="form-control" id="febrero3" name="febrero3"></td>
								      	<td><input type="text" class="form-control" id="marzo3" name="marzo3"></td>
								      	<td><input type="text" class="form-control" id="abril3" name="abril3"></td>
								      	<td><input type="text" class="form-control" id="mayo3" name="mayo3"></td>
								      	<td><input type="text" class="form-control" id="junio3" name="junio3"></td>
								    </tr>
								    <tr>
								      	<th scope="row"><span style="font-size :10px;">TIEMPO DE USO<br>(horas /día) </span></th>
								      	<td><input type="text" class="form-control" id="enero4" name="enero4"></td>
								      	<td><input type="text" class="form-control" id="febrero4" name="febrero4"></td>
								      	<td><input type="text" class="form-control" id="marzo4" name="marzo4"></td>
								      	<td><input type="text" class="form-control" id="abril4" name="abril4"></td>
								      	<td><input type="text" class="form-control" id="mayo4" name="mayo4"></td>
								      	<td><input type="text" class="form-control" id="junio4" name="junio4"></td>
								    </tr>
								    <tr>
								      	<th scope="row"><span style="font-size :10px;">LECTURA DEL MEDIDOR <br>(m3)</span></th>
								      	<td><input type="text" class="form-control" id="enero5" name="enero5"></td>
								      	<td><input type="text" class="form-control" id="febrero5" name="febrero5"></td>
								      	<td><input type="text" class="form-control" id="marzo5" name="marzo5"></td>
								      	<td><input type="text" class="form-control" id="abril5" name="abril5"></td>
								      	<td><input type="text" class="form-control" id="mayo5" name="mayo5"></td>
								      	<td><input type="text" class="form-control" id="junio5" name="junio5"></td>
								    </tr>
								    <tr>
								      	<th scope="row"><span style="font-size :10px;">VOLUMEN <br>(m3 /mes)</span></th>
								      	<td><input type="text" class="form-control" id="enero6" name="enero6"></td>
								      	<td><input type="text" class="form-control" id="febrero6" name="febrero6"></td>
								      	<td><input type="text" class="form-control" id="marzo6" name="marzo6"></td>
								      	<td><input type="text" class="form-control" id="abril6" name="abril6"></td>
								      	<td><input type="text" class="form-control" id="mayo6" name="mayo6"></td>
								      	<td><input type="text" class="form-control" id="junio6" name="junio6"></td>
								    </tr>
								</tbody>
							</table>
							<table class="table table-striped">
								<thead>
								    <tr>	
								    	<th scope="col" width="400">MES</th>							      
								      	<th scope="col">Julio</th>
								      	<th scope="col">Agosto</th>
								      	<th scope="col">Sept</th>
								      	<th scope="col">Oct</th>
								      	<th scope="col">Nov</th>
								      	<th scope="col">Dic</th>				

								    </tr>
								</thead>
								<tbody>
								    <tr>
								      	<th scope="row"><span style="font-size :10px;">FECHA DE LECTURA</span></th>
								        <td><input type="date" class="form-control" id="julio1" name="julio1"></td>
								      	<td><input type="date" class="form-control" id="agosto1" name="agosto1"></td>
								      	<td><input type="date" class="form-control" id="septiembre1" name="septiembre1"></td>
								      	<td><input type="date" class="form-control" id="octubre1" name="octubre1"></td>
								      	<td><input type="date" class="form-control" id="noviembre1" name="noviembre1"></td>
								      	<td><input type="date" class="form-control" id="diciembre1" name="diciembre1"></td>
								    </tr>
								    <tr>
								      	<th scope="row"><span style="font-size :10px;">PERIODO DE CLIMA<br> (Lluvia/ Sequia)</span></th>
								      	<td><input type="text" class="form-control" id="julio2" name="julio2"></td>
								      	<td><input type="text" class="form-control" id="agosto2" name="agosto2"></td>
								      	<td><input type="text" class="form-control" id="septiembre2" name="septiembre2"></td>
								      	<td><input type="text" class="form-control" id="octubre2" name="octubre2"></td>
								      	<td><input type="text" class="form-control" id="noviembre2" name="noviembre2"></td>
								      	<td><input type="text" class="form-control" id="diciembre2" name="diciembre2"></td>
								    </tr>
								    <tr>
								      	<th scope="row"><span style="font-size :10px;">PERIODO DE USO <br>(días / mes)</span> </th>
								        <td><input type="text" class="form-control" id="julio3" name="julio3"></td>
								      	<td><input type="text" class="form-control" id="agosto3" name="agosto3"></td>
								      	<td><input type="text" class="form-control" id="septiembre3" name="septiembre3"></td>
								      	<td><input type="text" class="form-control" id="octubre3" name="octubre3"></td>
								      	<td><input type="text" class="form-control" id="noviembre3" name="noviembre3"></td>
								      	<td><input type="text" class="form-control" id="diciembre3" name="diciembre3"></td>
								    </tr>
								    <tr>
								      	<th scope="row"><span style="font-size :10px;">TIEMPO DE USO<br>(horas /día) </span></th>
								        <td><input type="text" class="form-control" id="julio4" name="julio4"></td>
								      	<td><input type="text" class="form-control" id="agosto4" name="agosto4"></td>
								      	<td><input type="text" class="form-control" id="septiembre4" name="septiembre4"></td>
								      	<td><input type="text" class="form-control" id="octubre4" name="octubre4"></td>
								      	<td><input type="text" class="form-control" id="noviembre4" name="noviembre4"></td>
								      	<td><input type="text" class="form-control" id="diciembre4" name="diciembre4"></td>
								    </tr>
								    <tr>
									    <th scope="row"><span style="font-size :10px;">LECTURA DEL MEDIDOR<br> (m3)</span></th>
									    <td><input type="text" class="form-control" id="julio5" name="julio5"></td>
									    <td><input type="text" class="form-control" id="agosto5" name="agosto5"></td>
									    <td><input type="text" class="form-control" id="septiembre5" name="septiembre5"></td>
									    <td><input type="text" class="form-control" id="octubre5" name="octubre5"></td>
									    <td><input type="text" class="form-control" id="noviembre5" name="noviembre5"></td>
									    <td><input type="text" class="form-control" id="diciembre5" name="diciembre5"></td>
								    </tr>
								    <tr>
								    	<th scope="row"><span style="font-size :10px;">VOLUMEN <br>(m3 /mes)</span></th>						      
								      	<td><input type="text" class="form-control" id="julio6" name="julio6"></td>
								      	<td><input type="text" class="form-control" id="agosto6" name="agosto6"></td>
								      	<td><input type="text" class="form-control" id="septiembre6" name="septiembre6"></td>
								      	<td><input type="text" class="form-control" id="octubre6" name="octubre6"></td>
								      	<td><input type="text" class="form-control" id="noviembre6" name="noviembre6"></td>
								      	<td><input type="text" class="form-control" id="diciembre6" name="diciembre6"></td>
								    </tr>
								</tbody>
							</table>
						</div>
					</div>
					<div class="row">
						<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12">
							<h3>En caso de que el Tipo de Uso (Item 3) sea más de una opción, discrimine el porcentaje por Tipo de Uso respecto al Volumen Total Anual:</h3>
						</div>
					</div>
					<div class="row">																	
						<div class="form-group col-xs-12 col-sm-12 col-md-3 col-lg-3">
						    <label for="tipo_de_uso_01" class="control-label">Tipo de Uso #01: </label><br>
						    <input type="text" class="form-control" id="tipo_de_uso_01" name="tipo_de_uso_01" placeholder="Tipo de Uso #01">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-3 col-lg-3">
						    <label for="volumen_de_uso_01" class="control-label">Volumen de Uso #01 (%) </label><br>
						    <input type="text" class="form-control" id="volumen_de_uso_01" name="volumen_de_uso_01" placeholder="Volumen de Uso #01 (%)">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-3 col-lg-3">
						    <label for="tipo_de_uso_04" class="control-label">Tipo de Uso #04: </label><br>
						    <input type="text" class="form-control" id="tipo_de_uso_04" name="tipo_de_uso_04" placeholder="Tipo de Uso #04">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-3 col-lg-3">
						    <label for="volumen_de_uso_04" class="control-label">Volumen de Uso #04 (%) </label><br>
						    <input type="text" class="form-control" id="volumen_de_uso_04" name="volumen_de_uso_04" placeholder="Volumen de Uso #04 (%)">
						</div>
					</div>
					<div class="row">																	
						<div class="form-group col-xs-12 col-sm-12 col-md-3 col-lg-3">
						    <label for="tipo_de_uso_02" class="control-label">Tipo de Uso #02: </label><br>
						    <input type="text" class="form-control" id="tipo_de_uso_02" name="tipo_de_uso_02" placeholder="Tipo de Uso #02">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-3 col-lg-3">
						    <label for="volumen_de_uso_02" class="control-label">Volumen de Uso #02 (%) </label><br>
						    <input type="text" class="form-control" id="volumen_de_uso_02" name="volumen_de_uso_02" placeholder="Volumen de Uso #02 (%)">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-3 col-lg-3">
						    <label for="tipo_de_uso_05" class="control-label">Tipo de Uso #05: </label><br>
						    <input type="text" class="form-control" id="tipo_de_uso_05" name="tipo_de_uso_05" placeholder="Tipo de Uso #05">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-3 col-lg-3">
						    <label for="volumen_de_uso_05" class="control-label">Volumen de Uso #05 (%) </label><br>
						    <input type="text" class="form-control" id="volumen_de_uso_05" name="volumen_de_uso_05" placeholder="Volumen de Uso #05 (%)">
						</div>
					</div>
					<div class="row">																	
						<div class="form-group col-xs-12 col-sm-12 col-md-3 col-lg-3">
						    <label for="tipo_de_uso_03" class="control-label">Tipo de Uso #03: </label><br>
						    <input type="text" class="form-control" id="tipo_de_uso_03" name="tipo_de_uso_03" placeholder="Tipo de Uso #03">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-3 col-lg-3">
						    <label for="volumen_de_uso_03" class="control-label">Volumen de Uso #03 (%) </label><br>
						    <input type="text" class="form-control" id="volumen_de_uso_03" name="volumen_de_uso_03" placeholder="Volumen de Uso #03 (%)">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-3 col-lg-3">
						    <label for="tipo_de_uso_06" class="control-label">Tipo de Uso #06: </label><br>
						    <input type="text" class="form-control" id="tipo_de_uso_06" name="tipo_de_uso_06" placeholder="Tipo de Uso #06">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-3 col-lg-3">
						    <label for="volumen_de_uso_06" class="control-label">Volumen de Uso #06 (%) </label><br>
						    <input type="text" class="form-control" id="volumen_de_uso_06" name="volumen_de_uso_06" placeholder="Volumen de Uso #06 (%)">
						</div>
					</div>
				</div>
			</div>



			<div class="panel panel-default">
				<div class="panel-heading">
				    <h3 class="panel-title">7.  DATOS DEL SISTEMA DE MEDICION DE AGUA NO CONSUNTIVA</h3>
				</div>
				<div class="panel-body">
					<div class="row">
		            	<div class="form-group col-xs-12 col-sm-12 col-md-6 col-lg-6">
						    <label for="tipo_marca_sis_medicion2" class="control-label">Tipo  y Marca del sistema de medición </label><br>
						    <input type="text" class="form-control" id="tipo_marca_sis_medicion2" name="tipo_marca_sis_medicion2" placeholder="Tipo y Marca del sistema de medición">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-2 col-lg-2">
						    <label for="modelo" class="control-label">Modelo: </label><br>
						    <input type="text" class="form-control" id="modelo2" name="modelo2" placeholder="Modelo:">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-2 col-lg-2">
						    <label for="serie2" class="control-label">Série: </label><br>
						    <input type="text" class="form-control" id="serie2" name="serie2" placeholder="Série">
						</div>		
						<div class="form-group col-xs-12 col-sm-12 col-md-2 col-lg-2">
						    <label for="fecha_instalacion2" class="control-label">Fecha de Instalación: </label><br>
						    <input type="date" class="form-control" id="fecha_instalacion2" name="fecha_instalacion2" placeholder="Fecha de Instalación">
						</div>						
					</div>
					<div class="row">
		            	<div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4">
						    <label for="fecha_de_calibracion2" class="control-label">Fecha de calibración: </label><br>
						    <input type="date" class="form-control" id="fecha_de_calibracion2" name="fecha_de_calibracion2" placeholder="Fecha de calibración">
						</div>
						
						<div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4">
						    <label for="empresa_que_realiza_la_calibracion2" class="control-label">Empresa que realiza la Calibración:	 </label><br>
						    <input type="text" class="form-control" id="empresa_que_realiza_la_calibracion2" name="empresa_que_realiza_la_calibracion2" placeholder="Empresa que realiza la Calibración:">
						</div>
						<div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4">
		                    <div class="form-group">								                     
		                        <label for="vigencia_superior_dos_anos2">Vigencia superior a dos años: </label><br>		
		                        <label class="radio-inline"><input type="radio" name="vigencia_superior_dos_anos2" id="vigencia_superior_dos_anos2" value="SI">SI</label>&nbsp;&nbsp;&nbsp;
								<label class="radio-inline"><input type="radio" name="vigencia_superior_dos_anos2" id="vigencia_superior_dos_anos2" value="NO">NO</label>	
		                    </div>
		                </div>
					</div>

				</div>
			</div>
			<div class="panel panel-default">
				<div class="panel-heading">
				    <h3 class="panel-title">8. REPORTE MENSUAL DE VOLÚMENES DE AGUA NO CONSUNTIVA</h3>
				</div>
				<div class="panel-body">
					
					
					
					<div class="row">
						<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12">
							<table class="table table-striped">
								<thead>
								    <tr>
								      	<th scope="col" width="400">MES</th>
								      	<th scope="col">Enero</th>
								      	<th scope="col">Febrero</th>
								      	<th scope="col">Marzo</th>
								      	<th scope="col">Abril</th>
								      	<th scope="col">Mayo</th>
								      	<th scope="col">Junio</th>
								  	</tr>
								</thead>
								<tbody>
								    <tr>
								      	<th scope="row"><span style="font-size :10px;">FECHA DE LECTURA</span></th>
								      	<td><input type="date" class="form-control" id="venero1" name="venero1"></td>
								      	<td><input type="date" class="form-control" id="vfebrero1" name="vfebrero1"></td>
								      	<td><input type="date" class="form-control" id="vmarzo1" name="vmarzo1"></td>
								      	<td><input type="date" class="form-control" id="vabril1" name="vabril1"></td>
								      	<td><input type="date" class="form-control" id="vmayo1" name="vmayo1"></td>
								      	<td><input type="date" class="form-control" id="vjunio1" name="vjunio1"></td>
								  	</tr>
								    <tr>
								      	<th scope="row"><span style="font-size :10px;">LECTURA DEL MEDIDOR <br>(m3)</span></th>
								      	<td><input type="text" class="form-control" id="venero2" name="venero2"></td>
								      	<td><input type="text" class="form-control" id="vfebrero2" name="vfebrero2"></td>
								      	<td><input type="text" class="form-control" id="vmarzo2" name="vmarzo2"></td>
								      	<td><input type="text" class="form-control" id="vabril2" name="vabril2"></td>
								      	<td><input type="text" class="form-control" id="vmayo2" name="vmayo2"></td>
								      	<td><input type="text" class="form-control" id="vjunio2" name="vjunio2"></td>
								  	</tr>
								    <tr>
								      	<th scope="row"><span style="font-size :10px;">VOLUMEN <br>(m3 /mes)</span></th>
								      	<td><input type="text" class="form-control" id="venero3" name="venero3"></td>
								      	<td><input type="text" class="form-control" id="vfebrero3" name="vfebrero3"></td>
								      	<td><input type="text" class="form-control" id="vmarzo3" name="vmarzo3"></td>
								      	<td><input type="text" class="form-control" id="vabril3" name="vabril3"></td>
								      	<td><input type="text" class="form-control" id="vmayo3" name="vmayo3"></td>
								      	<td><input type="text" class="form-control" id="vjunio3" name="vjunio3"></td>
								  </tr>   
								</tbody>
							</table>
							<table class="table table-striped">
								<thead>
								    <tr>
								      	<th scope="col" width="400">MES</th>
								      	<th scope="col">Julio</th>
								      	<th scope="col">Agosto</th>
								      	<th scope="col">Sept</th>
								      	<th scope="col">Oct</th>
								      	<th scope="col">Nov</th>
								      	<th scope="col">Dic</th>
								  	</tr>
								</thead>
								<tbody>
								    <tr>
								      	<th scope="row"><span style="font-size :10px;">FECHA DE LECTURA</span></th>
								      	<td><input type="date" class="form-control" id="vjulio1" name="vjulio1"></td>
								      	<td><input type="date" class="form-control" id="vagosto1" name="vagosto1"></td>
								     	<td><input type="date" class="form-control" id="vseptiembre1" name="vseptiembre1"></td>
								      	<td><input type="date" class="form-control" id="voctubre1" name="voctubre1"></td>
								      	<td><input type="date" class="form-control" id="vnoviembre1" name="vnoviembre1"></td>
								      	<td><input type="date" class="form-control" id="vdiciembre1" name="vdiciembre1"></td>
								    </tr>
								    <tr>
								      	<th scope="row"><span style="font-size :10px;">LECTURA DEL MEDIDOR <br>(m3)</span></th>
								      	<td><input type="text" class="form-control" id="vjulio2" name="vjulio2"></td>
								      	<td><input type="text" class="form-control" id="vagosto2" name="vagosto2"></td>
								      	<td><input type="text" class="form-control" id="vseptiembre2" name="vseptiembre2"></td>
								      	<td><input type="text" class="form-control" id="voctubre2" name="voctubre2"></td>
								      	<td><input type="text" class="form-control" id="vnoviembre2" name="vnoviembre2"></td>
								      	<td><input type="text" class="form-control" id="vdiciembre2" name="vdiciembre2"></td>
								    </tr>
								    <tr>
								      	<th scope="row"><span style="font-size :10px;">VOLUMEN <br>(m3 /mes)</span></th>
								      	<td><input type="text" class="form-control" id="vjulio3" name="vjulio3"></td>
								      	<td><input type="text" class="form-control" id="vagosto3" name="vagosto3"></td>
								      	<td><input type="text" class="form-control" id="vseptiembre3" name="vseptiembre3"></td>
								      	<td><input type="text" class="form-control" id="voctubre3" name="voctubre3"></td>
								      	<td><input type="text" class="form-control" id="vnoviembre3" name="vnoviembre3"></td>
								      	<td><input type="text" class="form-control" id="vdiciembre3" name="vdiciembre3"></td>
								    </tr>   
								</tbody>
							</table>
						</div>
					</div>
					<div class="row">
					  <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12">
					    <div class="form-group">
					        <label class="control-label">Adjuntar archivo(s)</label>
					        
					        <div class="attachment-row cajainput">
					          <input type="file" class="form-control margintop" name="attachment[]" accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,image/jpeg" onchange="return fileValidation(this.value)">
					        </div>
					        <div onclick="AgregarArchivos();" class="icon-add-more-attachemnt margintop" title="Agregar más archivos"> <img src="images/agregar-archivo.png" alt="Agregar más archivos"> Agregar mas archivos </div>
					        <div class="help-block with-errors"></div>
					      </div>
					    </div>
					</div>
										
				</div>
			</div>
				
						

												


<!---
			<div class="panel panel-default">
				<div class="panel-heading">
				    <h3 class="panel-title">6.    DECLARACIÓN JURAMENTADA:</h3>
				</div>
				<div class="panel-body">
					
				</div>
			</div>--->		
					
				

       
       
         




           

           <div class="row lineados" id="botonguardar">
                <div class="form-group col-xs-6 col-sm-6 col-md-6 col-lg-12">    
                <div class="g-recaptcha" data-sitekey="6LeNX1spAAAAAB84iEHuF8cSa7Frgwm9qKvcQks-"></div>  
                    <button class="btn btn-primary" id="boton" name="boton" type="submit">
                        Enviar
                    </button>
                    <input name="action" type="hidden" value="upload">
                </div>
                <div class="form-group col-xs-6 col-sm-6 col-md-12 col-lg-12">
                    <label><span style="font-size:12px; color: #009900;">* Campo Obligatorio</span></label>
                </div>                
            </div>
            <div class="row">
            	<div class="form-group col-xs-6 col-sm-6 col-md-12 col-lg-12" id="guardando"></div>
            </div>
             
        </form>