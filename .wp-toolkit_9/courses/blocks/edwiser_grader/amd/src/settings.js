define(['jquery', 'core/ajax', 'core/templates'], function($, Ajax, templates) {
    function init() {
        let ausers = [];
        let lusers = [];
        loadUsers();

        $('#edg-ausers-select').on('change', function() {
            $('.edg-add-user-btn').removeAttr("disabled");
        });
        $('#edg-lusers-select').on('change', function() {
            $('.edg-remove-user-btn').removeAttr("disabled");
        });

        function loadUsers() {
            $("#edg-ausers-select option").each(function () {
                let id = $(this).val();
                let email = $(this).text();
                ausers.push({ id: id, email: email});
            });

            $("#edg-lusers-select option").each(function () {
                let id = $(this).val();
                let email = $(this).text();
                lusers.push({ id: id, email: email});
            });
        }

        $('.edg-ausers-input').on('input', function () {
            let searchText = $(this).val();
            let fausers = ausers.filter(user => {
                return user.email.toLowerCase().includes(searchText.toLowerCase(), 0);
            });
            $("#edg-ausers-select").html("");
            fausers.forEach(function (user, index) {
                $("#edg-ausers-select").append(`<option value="${user.id}">${user.email}</option>`);
            });
        });

        $('.edg-lusers-input').on('input', function () {
            let searchText = $(this).val();
            let flusers = lusers.filter(user => {
                return user.email.toLowerCase().includes(searchText.toLowerCase(), 0);
            });
            $("#edg-lusers-select").html("");
            flusers.forEach(function (user, index) {
                $("#edg-lusers-select").append(`<option value="${user.id}">${user.email}</option>`);
            });
        });
        var currentSite  = M.cfg.wwwroot.replace(/(^\w+:|^)\/\//, '');
        $("#edg-lsites-box").val(currentSite);
        $("#edg-lsites-box").change(function() {
            $(".edg-lusers-skel").show();
            $(".edg-lusers-name").hide();
            let selectedSite = $('option:selected', this).val();
            if (selectedSite !== currentSite) {
                $(".edg-ausers").hide();
                $(".edg-user-actions").attr('style','display:none !important');
                $(".edg-site-alert").show();
            } else {
                $(".edg-site-alert").hide();
                $(".edg-ausers").show();
                $(".edg-user-actions").attr('style','display:flex !important');
            }
            let service_name = 'block_edwiser_grader_get_licensed_users';
            var getLicensedUsers = Ajax.call([
                    {
                        methodname: service_name,
                        args: {
                            selectedsite : selectedSite,
                        }
                    }
                ]);
                getLicensedUsers[0].done(function (response) {
                    let template = "block_edwiser_grader/licensedusers";
                    templates.render(template, response).then(
                        (html, js)  => {
                            templates.replaceNode($("#edg-lusers-select"), html , js);
                            $(".edg-lusers-name").show();
                            $(".edg-lusers-skel").hide();
                        }
                    );
                });
        });
    }
    return {
        init: init
    };
});