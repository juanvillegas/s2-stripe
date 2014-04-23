s2-stripe
=========

S2 Stripe - Thin layer between S2 Member and Stripe.com payments

Intro
------

This is a plugin I made for Wordpress to integrate Stripe payments into Wordpress. It all started after a request from a client of mine, for his own personal website. He wanted to replace Paypal forms with Stripe payments, and as everyone knows, S2 isnt supporting this gateway yet (and my guess is that they wont in the near future). So we succesfully built a bspoke implementation and then I ported the whole code to a wordpress plugin, with a few improvements, shortcodes, etc.

At first, I was looking to sell this and make some $$ in return, but my full time work at SheologyDigital.com wont allow me to reply to support tickets in time. So here is the github repo for it, free for everyone.

Feel free to fork, extend, and make this better. Im going to check and accept pull requests (if any).

Roadmap
------
+ Connect **unsubscribe** event, sent from Stripe, to the corresponding S2 unsusbcribe event
+ Add switch in Settings > S2 Stripe to allow easily changing to test/production mode
+ Add custom error messages
+ Add localization

Docs
------

### Detecting when a customer's subscription should end

Stripe will handle charging your customers when the period (month, semester, year, whatever) ends. If Stripe fails to charge your customer, it will send a notification to an endpoint of your choice. So, what you have to do to receive these notifications in your S2 installation is configuring the endpoint to point to your site url. We are going to add a specific query string parameter so the plugin can distinguish these notifications from any other POST request..

Configuring the endpoint is as simple as:

1.Go to your Stripe dashboard
2.Go to **account settings**
3.Click the **webhooks** tab
4.Click the **add url** button
5.In the URL box enter your site url (the same returned by site_url()) and append __?s2_stripe_listener=1__
6.Click the **Create Webhook URL** to create the webhook
7.Done

Screenshot: http://prntscr.com/3cjigx

From now on, all events happening in Stripe will be sent to your site, and the plugin will catch these requests and act accordingly. For the moment, the only event we are interested in is the __Subscription Cancellation__ event.

<< Working here >>

