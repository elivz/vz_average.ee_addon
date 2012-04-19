VZ Average
==========

*This is beta software, use at your own risk. But please send along suggestions and bug reports.*

VZ Average is a flexible and  minimal framework for rating or tallying anything in EE that you can identify with an ID: a channel entry, a comment, a Matrix row, or anything else. A few possible uses for it include: star ratings, thumbs up/down ratings, flagging comments as offensive, voting, etc. 

It provides a simple template tag that generates a form for submitting ratings, plus several tags to display data about the ratings. I have avoided outputting any UI (unlike the other ratings add-ons out there), focussing instead on providing an abstracted data store that you can use imaginatively to achieve a wide range of effects.

When used to rate a channel entry, VZ Average can also update a custom field with the average, sum, minimum, maximum, or total number of ratings. Although any of these can also be output using VZ Average's template tags, mirroring them into a custom field will allow you to search or sort entries based on the number.


Installation
------------

Copy the VZ Average directory into /system/expressionengine/third_party/. Enable the module in the control panel.


{exp:vz_average:form} tag pair
------------------------------

This tag pair generates the form that you will use to submit a new rating. It only generates the form tags and some hidden fields, it is up to you to add an input or select tag with `name="value"` that will pass in the actual numeric value of the rating. See below for examples.

### entry_id = [integer]

(required) The unique ID of the thing you are rating. Most often, this will be the {entry_id} of a channel entry or the {comment_id} of a comment, but it can be any unique identifier.

### entry_type = [string]

This is essentially a namespace for the `entry_id`. If you are storing ratings for both channel entries and comments, there is a good chance their IDs will overlap, so you would set `entry_type="comment"` for the comment rating forms to keep their data separate. You can use any and as many entry_types as you see fit.

### form_id, form_class

These simply set their respective HTML attributes on the form tag, for styling or Javascript purposes.

### return

The path to redirect to after the form is submitted. If this parameter is not set, it will default to returning to the current page.

### limit_by = [ip|member]

If you wish to prevent people from voting more than once, you can set this parameter to either `ip` or `member`. The `member` setting means that only votes from logged-in members will be accepted, so you may want to make use of the {logged_in} condition to hide or disable the form for anonymous users.

Something to keep in mind: When a duplicate vote is detected, it will *replace* that person's previous vote. This means that a person can update their previous rating.

### max, min

You can set maximum and minimum limits on the acceptable ratings. Any rating higher than the maximum or lower than the minimum will be changed to keep it within the set range.

### update_field = [field short_name]

The `update_field` parameter lets you store the resulting average (or sum, max, etc.) in a custom field within the entry. In order for this to work, the `entry_id` must be the actual id of a channel entry. This parameter should be set to the short_name of the field you wish to update.

*Warning: Please do not use this when storing ratings for anything other than channel entries. There is a very good chance you will end up corrupting your channel data.*

### update_with = [average|total|min|max|count]

The type of calculation to save in the custom field. This can be one of: `average`, `sum`, `min`, `max`, or `count`.


{exp:vz_average:average}, {exp:vz_average:sum}, {exp:vz_average:min}, {exp:vz_average:max}, {exp:vz_average:count}
------------------------------------------------------------------------------------------------------------------

All of these retrieve calculated values for a particular entry. They each take the same two paramters:

### entry_id

(required) The unique ID of the entry to be displayed.

### entry_type

The type of entry to be displayed. This defaults to `channel`.

### min, max (only for {exp:vz_average:average})

If you specify a `min` and a `max`, the `{exp:vz_average:average}` tag will return a percentage rather than a fixed number. This is particularly useful for generating bar graphs, as can be seen in the first example below.


AJAX
----

You can easily POST the VZ Average form using AJAX, in which case the return value will be a JSON string containing all the calculated values, which can be decoded into the following object:

    {
        'average': 0,
        'sum': 0,
        'min': 0,
        'max': 0,
        'count': 0
    }


Examples
--------
    
### Tally votes

    <h2>{title} has received {exp:vz_average:count entry_id="{entry_id}"} votes!</h2>
    {if logged_in}
        {exp:vz_average:form entry_id="{entry_id}" limit_by="member"}
            <input type="hidden" name="value" value="1" />
            <input type="submit" value="Vote for {title}!" />
        {/exp:vz_average:form}
    {if:else}
        <p class="alert">You must be logged in to vote :(</p>
    {/if}

### Simple 5-star rating widget

The `rating` custom field will be updated with the new average rating each time, allowing for the entries to be sorted from highest- to lowest-rated. 

    <div class="star-rating" style="position:relative; background:#00f;">
        <meter max="5" min="0" style="width:{exp:vz_average:average entry_id="{entry_id}" min="0" max="5"}%; background:#ff0;" value="{rating}">
            {rating} out of 5
        </meter>
    </div>
    {exp:vz_average:form entry_id="{entry_id}" limit_by="ip" min="0" max="5" update_field="rating" update_type="average"}
        <label for="rating_{entry_id}_1">1</label>
        <input id="rating_{entry_id}_1" name="value" type="radio" value="1" />
        <label for="rating_{entry_id}_2">2</label>
        <input id="rating_{entry_id}_2" name="value" type="radio" value="2" />
        <label for="rating_{entry_id}_3">3</label>
        <input id="rating_{entry_id}_3" name="value" type="radio" value="3" />
        <label for="rating_{entry_id}_4">4</label>
        <input id="rating_{entry_id}_4" name="value" type="radio" value="4" />
        <label for="rating_{entry_id}_5">5</label>
        <input id="rating_{entry_id}_5" name="value" type="radio" value="5" />
    {/exp:vz_average:form}

### Allow users to flag comments as "offensive"

    <ol>{exp:comment:entries entry_id="{entry_id}" status="not Closed"}
        <li class="comment" id="comment_{comment_id}">
            <div class="author">By {url_as_author}</div>
            <div class="comment_body">{comment}</div>
            <div class="published"><time>{comment_date format="%F %j, %Y"}</time></div>
            {exp:vz_average:form entry_id="{comment_id}" entry_type="comment" limit_by="ip"}
                <input type="hidden" name="value" value="1" />
                <input type="submit" value="Flag as Offensive" />
            {/exp:vz_average:form}
        </li>
    {/exp:comment:entries}</ol>

Note that this will not automatically close comments when they reach a certain number of "flags". That can be done with an experimental modification to VZ Average's core code. Edit `mod.vz_average.php` and add the following code beginning on line 194:
        
    // MOD: close comments with enough flags
    if ($entry_type == 'comment') {
        if ($cumulative['count'] > 0)
        {
            $status = 'p';
        }
        elseif ($cumulative['count'] > 2) // Change the "2" to however many flags it takes to trigger the filter
        {
            $status = 'c';
        }
        else
        {
            $status = 'o';
        }
        
        $this->EE->db->update(
            'exp_comments',
            array('status' => $status),
            array('comment_id' => $entry_id, 'site_id' => $this->EE->input->post('site_id'))
        );
    }


Roadmap
-------

In no particular order, these are features I hope to add in the relatively near future. If anyone has other suggestions, or wants to tackle one of these and submit a pull-request, so much the better.

* A CP interface to view rated items. Sort by most rated, highest average, etc.
* Support saving data to more than one custom field at a time.
* Add more data outputs including [weighted scoring](http://evanmiller.org/how-not-to-sort-by-average-rating.html) for better entry sorting.
* More/better code examples for different scenarios.
* Use the current entry if no entry_id is set.
* Template tags providing access to the current user's previous ratings.
* Support the `secure_action` parameter for SSL pages.
* Hooks (what do y'all want?)