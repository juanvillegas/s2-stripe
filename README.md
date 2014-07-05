s2-stripe
=========

S2 Stripe - Thin layer between S2 Member and Stripe.com payments

Intro
------

This is a plugin I made for Wordpress to integrate Stripe payments into S2 Member plugin. It all started after a request from a client of mine, for his own personal website. He wanted to replace Paypal forms with Stripe payments, and as everyone knows, S2 isnt supporting this gateway yet (and my guess is that they wont in the near future). So we succesfully built a bspoke implementation and then I ported the whole code to a wordpress plugin, with a few improvements, shortcodes, etc.

At first, I was looking to sell this and make some $$ in return, but my full time work at SheologyDigital.com wont allow me to reply to support tickets in time. So here is the github repo for it, free for everyone.

Feel free to fork, extend, and make this better. Im going to check and accept pull requests (if any).

Intro++
------
The plugin allows the site owner to:
+ Create a form that visitors will use to register and pay with their credit card, using Stripe as processor. Once the payment is processed the plugin connects the operation to S2 Member, just as if your customer had paid with Paypal.
+ Provide their customers with an upgrade/downgrade form, allowing them to change the current subscription they are tied to. Once the customer changes his subscription, both the Stripe customer and the corresponding S2 Member user is updated.
+ Provide an unsubscribe link users can click to cancel their subscription from both Stripe and S2 Member.
+ A custom webhook that listens for requests from Stripe and acts accordingly. For example, if Stripe failed to cancel an invoice (because the user's cc doesnt have any funds, etc), then it will send the corresponding notification to your Wordpress site, and the plugin will demote the user to the Free membership. All this is automatic and the administrator doesnt have to do anything!

Roadmap
------
+ [DONE] Connect **unsubscribe** event, sent from Stripe, to the corresponding S2 unsusbcribe event
+ [DELAYED TO V2] Add switch in Settings > S2 Stripe to allow easily changing to test/production mode
+ [DELAYED TO V2] Add custom error messages
+ [DELAYED TO V2] Add localization
+ [DONE] Add self-updater
+ [WORKING] Make form markup standard
+ [WORKING] Test it a bit more :$

FAQ
------
+ Check ISSUES section for topics tagged "question"

Docs
------

### Installation

1. Install the plugin as you install any other Wordpress plugin
2. Go to Settings > S2 Stripe
3. Enter your Stripe keys
4. Enter your S2 IPN key (find it under S2 Member > Paypal Options > Paypal IPN Integration)
5. If you havent created your Stripe Plans, go and create them in your Stripe.com dashboard
6. Go back to Settings > S2 Stripe
  1. Go to the Plans Map section
  2. Click *Map Another* link to enter a new mapping
  3. In the left combo, choose a *S2 Role/Membership*. In the right combo, pick a *Stripe Plan* to associate with your S2 Role/Membership previously chosen.
  4. Repeat step 6.2 and 6.3 for all the mappings you need, and once finished click *Save*.
7. (Optional) But important: this step allows you to configure your plugin to listen for subscription cancellations in Stripe to end the corresponding S2 Member User subscription. Refer to *Detecting when a customer's subscription should end* for the steps to follow.

### Videos


[![ScreenShot](http://img.youtube.com/vi/087KKv8evUk/0.jpg)](http://youtu.be/087KKv8evUk)
[![ScreenShot](http://img.youtube.com/vi/v4QGZvtwGQQ/0.jpg)](http://youtu.be/v4QGZvtwGQQ)
[![ScreenShot](http://img.youtube.com/vi/zPuBDEcWe0I/0.jpg)](http://youtu.be/zPuBDEcWe0I)
[![ScreenShot](http://img.youtube.com/vi/btoVqT-JvX4/0.jpg)](http://youtu.be/btoVqT-JvX4)
[![ScreenShot](http://img.youtube.com/vi/84RnqVZOvNQ/0.jpg)](http://youtu.be/84RnqVZOvNQ)

### How to use it

The plugin will enable two shortcodes:
* **[s2_stripe_pay]**
* **[s2_stripe_upgrade]**

#### s2_stripe_pay

Generates a pay form to be embedded in any page/post. The form has the required classes and ids to be properly styled and referenced with scripts. An example of the output can be seen here: <<needs screenshot>>
There are a few parameters to be used with this shortcode:
* *s2_level*: it's the role the form is going to be created for. This parameter determines which role the new user is subscribing to, and thus how much he will be charged. (required)
* *submit_label*: it's the label to be used in the submit button. (optional. defaults to "Submit")
* *coupons_enabled*: indicates wether or not the form should give the option to the user to enter coupons. (option. defaults to "false")

#### s2_stripe_upgrade

Generates an upgrade form to be embedded in any page/post. The form will promp the user to enter his details and the target plan (thus, allows him to upgrade or downgrade).
Parameters:
* *label*: The label to use in the submit button. (optional. defaults to "Upgrade/Downgrade")
* *coupons_enabled*: same as above. (optional. defaults to "false")

### Detecting when a customer's subscription should end

Stripe will handle charging your customers when the period (month, semester, year, whatever) ends. If Stripe fails to charge your customer, it will send a notification to an endpoint of your choice. So, what you have to do to receive these notifications in your S2 installation is configuring the endpoint to point to your site url. We are going to add a specific query string parameter so the plugin can distinguish these notifications from any other POST request..

Configuring the endpoint is as simple as:

1. Go to your Stripe dashboard
2. Go to **account settings**
3. Click the **webhooks** tab
4. Click the **add url** button
5. In the URL box enter your site url (the same returned by site_url()) and append *?s2_stripe_listener=1*
6. Click the **Create Webhook URL** to create the webhook
7. Done

Screenshot: http://prntscr.com/3cjigx

From now on, all events happening in Stripe will be sent to your site, and the plugin will catch these requests and act accordingly. For the moment, the only event we are interested in is the *Subscription Cancellation* event.

Need more help?
======
Im going to do my best to maintain and attend all issues anyone may have.. however, im working full time for a company, which usually turns into 9-10 hours a day, so i cannot compromise to help everyone/answer every doubt/keep the docs tidy..

*If you feel you need quick, additional, personalized support, feel free to contact me at my email juan.villgs[at]gmail[dot]com or check https://www.odesk.com/users/~~3df1e9f3d65951f7 for an hourly fee.*

