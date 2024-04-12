# wp-authentik-teams
Wordpress Plugin for Team-Management with Authentik IAM.
### Scenario
Authentik is used as IAM.
You run a WordPress website whose user management is handled by Authentik.
You now want users to be able to collect themselves in teams so that they can be grouped in other services (e.g. gitea organisations) using Authentik.
This plugin was developed for the [PhaKIR Challenge](https://phakir.re-mic.de/), a subchallenge of the MICCAI 2024. 
### Required services
[Authentik](https://goauthentik.io/), Version 2024.2.2, respectivly API v3

[Wordpress](https://wordpress.com/), Version 6.5.2 , PHP 7.4

## Installation
The plugin works as it is, but the appropriate vendors must be installed and the Authentik API code generated.
### Configure authentikation mechanism
#### Authentik
  - follow the [official guide](https://docs.goauthentik.io/integrations/services/wordpress/)
  - Advance the OpenID Scope: <code> email profile openid phakirwp </code>
  - To make this plugin work, the username must match between wordpress and authentik. Therefore you have to add a Customization->Property Mappings:
      - name: authentik wordpress OAuth Mapping: OpenID 'phakirwp'
      - scopename: phakirwp
      - expression (add all your custom scope values here): <code>wp_claims = {} \
        wp_claims["preferred_username"] = request.user.username \
        return wp_claims
        </code>
#### Wordpress
- We have used [OpenID Connect Generic](https://github.com/oidc-wp/openid-connect-generic).
- be aware, that local users or users with non-authentik usernames cannot use the plugin
### Prepare the plugin
- Clone the repository
- Install the  the [OpenAPI Generator](https://openapi-generator.tech/) 
<code> npm install @openapitools/openapi-generator-cli -g </code>
- Generate your php code
  <code> openapi-generator-cli generate -g php -o out -i https://..authentik-url..>/api/v3/schema/ </code>
- If you have not already done so, install the php packages (e.g. <code> apt install php-curl php-xml </code> )
- Install the PHP dependency manager [Composer](https://getcomposer.org/)  : \
<code>php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
 php -r "if (hash_file('sha384', 'composer-setup.php') === '8a6138e2a05a8c28539c9f0fb361159823655d7ad2deecb371b04a83966c61223adc522b0189079e3e9e277cd72b8897') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"\
 php composer-setup.php \
 php -r "unlink('composer-setup.php');" \
</code>
- Now compose :) <code>  sudo composer update </code>

You should now have compiled everything and your plugin is ready to be uploaded to Wordpress: <code>/wp-content/plugins/authentik-teams</code>.

### setup the plugin
The plugin has a settings page that can be found in the "Settings/Authentic Teams" dashboard-menu in Wordpress.
- Authentik API Token: Insert your api token here. You can create one in Authentik under Directory/Tokens and App passwords
- Authentik Base URL: .. by now this has now impact .. maybe we can replace the url instead of the openapigenerator, but the plugin will die on api changes..
- Admin Prefix: in our project, we don't want the users to see the admins, when searching for team members, so we hide them. All users with the given prefix will not be shown and cannot use the plugin.
- Only Allow active users: if checked, only users, that are marked as active in Authentik will be displayed.
- Group Prefix: To keep up the overview, we put this prefix in front of all groups in Authentik but remove it before displaying in Worpress.

## Usage / Information
- The settings configuration-test requires the Default Admin: akadmin to exist (can be inactive).
- Use it in wordpress with the shortcode: [all_teams]
- You see two tables. left table are all groups without you. right table are all groups you are member of.
- You can click on a table-row to navigate to the edit page of the group. If you are the team-leader, you can add and remove members and save the changes. If you are not the leader, you can just view.
- You can create a team. The member who creates the team, has to be in the team itself. He will be the team-leader (saved as attribute in the Authentik-group).  