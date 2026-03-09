export interface Translations {
    common: {
        save: string;
        cancel: string;
        delete: string;
        edit: string;
        add: string;
        create: string;
        update: string;
        submit: string;
        view: string;
        close: string;
        loading: string;
        saving: string;
        deleting: string;
        search: string;
        actions: string;
        export: string;
        reset_filters: string;
        no_results: string;
        confirm: string;
        success: string;
        error: string;
        warning: string;
        fill_details: string;
        view_details: string;
    };
    nav: {
        platform: string;
        dashboard: string;
        employees: string;
        positions: string;
        departments: string;
        repository: string;
        documentation: string;
        settings: string;
        logout: string;
        profile: string;
    };
    auth: {
        login: string;
        login_title: string;
        login_description: string;
        email: string;
        password: string;
        remember_me: string;
        forgot_password: string;
    };
    employees: {
        title: string;
        add: string;
        edit: string;
        view: string;
        search_placeholder: string;
        delete_confirm: string;
        columns: {
            name: string;
            email: string;
            department: string;
            position: string;
            hire_date: string;
            created_at: string;
            updated_at: string;
        };
    };
    departments: {
        title: string;
        add: string;
        edit: string;
        view: string;
        search_placeholder: string;
        delete_confirm: string;
        columns: {
            name: string;
            created_at: string;
            updated_at: string;
        };
    };
    positions: {
        title: string;
        add: string;
        edit: string;
        view: string;
        search_placeholder: string;
        delete_confirm: string;
        columns: {
            name: string;
            created_at: string;
            updated_at: string;
        };
    };
    form: {
        name: string;
        name_placeholder: string;
        email: string;
        email_placeholder: string;
        required: string;
    };
    dialog: {
        are_you_sure: string;
        delete_title: string;
    };
    pagination: {
        previous: string;
        next: string;
        page: string;
        of: string;
        rows_per_page: string;
        showing: string;
        to: string;
        entries: string;
    };
    table: {
        no_data: string;
        select_all: string;
        selected: string;
    };
    language: {
        switch: string;
        en: string;
        id: string;
    };
}
