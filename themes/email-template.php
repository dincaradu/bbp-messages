<?php

// Prevent direct access
defined('ABSPATH') || exit;

?>

<table border="0" cellpadding="0" cellspacing="0" style="width: 100%;background: #F1F1F1;padding: 20px 0;">

	<tr>

		<td>
			
			<table style="border: 1px solid #ddd; font-family:'Proxima Nova','Open Sans','Helvetica Neue',Calibri,Helvetica,sans-serif;color: #505050;background-color: #FDFDFD;box-shadow: 0 0 3px #ddd;margin: 0 auto;border-radius: 3px;-webkit-border-radius: 3px;" border="0" cellpadding="0" cellspacing="0">
	
				<tr>

					<td style="border-bottom: 1px solid #ddd;padding-top: 15px;padding-bottom: 3px;text-align: center;">

						<h2>
							<a href="{site_link}" style="text-decoration: none;color: #737373;">{site_name}</a>
						</h2>

						<h4 style="margin-bottom: 15px;margin-top: -8px;color: #969696;">{site_description}</h4>

					</td>

				</tr>

				<tr>

					<td style="border-bottom: 1px solid #ddd;padding: 35px 40px 40px;">

						<?php echo bbpm_settings()->notifications->body; ?>

					</td>

				</tr>

				<tr>

					<td style="padding: 15px;text-align: center;font-size: 14px;color: #808080;">

						<p>
							Copyright &copy; <?php echo date('Y'); ?> {site_name}
						</p>

					</td>

				</tr>

			</table>

		</td>

	</tr>

</table>