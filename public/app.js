// Every HTTP call lives in this object, so the rest of the file never calls fetch() directly.
const api = {
    async getTitles() {
        const response = await fetch('/api/titles');
        // fetch() only fails on network errors, not on a 404 or 500, so the status is checked here.
        if (!response.ok) {
            throw new Error(`Could not load titles (status ${response.status}).`);
        }

        return response.json();
    },

    async createTitle(title) {
        const response = await fetch('/api/titles', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(title),
        });
        if (response.status === 400) {
            const { errors } = await response.json();
            throw new Error(Object.values(errors).join(' '));
        }
        if (!response.ok) {
            throw new Error(`Could not add the title (status ${response.status}).`);
        }

        return response.json();
    },

    async updateWatched(id, watched) {
        const response = await fetch(`/api/titles/${id}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ watched }),
        });
        if (!response.ok) {
            throw new Error(`Could not update the title (status ${response.status}).`);
        }

        return response.json();
    },

    async deleteTitle(id) {
        const response = await fetch(`/api/titles/${id}`, { method: 'DELETE' });
        if (!response.ok) {
            throw new Error(`Could not delete the title (status ${response.status}).`);
        }
    },
};

const typeLabels = { film: 'Film', series: 'Series' };

const titleForm = document.getElementById('title-form');
const titleList = document.getElementById('title-list');
const errorMessage = document.getElementById('error-message');

// The highest allowed year moves with the calendar, so it is set here instead of in the HTML.
// It matches YEARS_AHEAD in TitleController.
titleForm.elements.year.max = new Date().getFullYear() + 5;

function renderTitles(titles) {
    titleList.replaceChildren();

    if (titles.length === 0) {
        const cell = titleList.insertRow().insertCell();
        cell.colSpan = 5;
        cell.textContent = 'No titles yet.';
        return;
    }

    for (const title of titles) {
        const row = titleList.insertRow();
        // textContent, never innerHTML: a name like "<script>" is shown as text instead of being run.
        row.insertCell().textContent = title.name;
        row.insertCell().textContent = typeLabels[title.type];
        row.insertCell().textContent = title.year;

        const watchedCheckbox = document.createElement('input');
        watchedCheckbox.type = 'checkbox';
        watchedCheckbox.checked = title.watched;
        // The checkbox has no visible text, so screen readers need this label.
        watchedCheckbox.setAttribute('aria-label', `Watched: ${title.name}`);
        watchedCheckbox.addEventListener('change', () => handleWatchedChange(title, watchedCheckbox));
        row.insertCell().append(watchedCheckbox);

        const deleteButton = document.createElement('button');
        deleteButton.textContent = 'Delete';
        // The arrow function remembers this row's title, so each button deletes its own title.
        deleteButton.addEventListener('click', () => handleDelete(title));
        row.insertCell().append(deleteButton);
    }
}

function showError(message) {
    errorMessage.textContent = message;
    errorMessage.hidden = false;
}

function hideError() {
    errorMessage.hidden = true;
}

// Called after every change, so the page always shows what the server has.
async function loadTitles() {
    hideError();
    try {
        renderTitles(await api.getTitles());
    } catch (error) {
        showError(error.message);
    }
}

async function handleWatchedChange(title, checkbox) {
    // Blocks a second click while the first request is still running.
    checkbox.disabled = true;

    try {
        await api.updateWatched(title.id, checkbox.checked);
        await loadTitles();
    } catch (error) {
        // The checkbox already flipped in the browser, so reload to show the real state again.
        await loadTitles();
        showError(error.message);
    }
}

async function handleDelete(title) {
    if (!confirm(`Delete "${title.name}"?`)) {
        return;
    }

    try {
        await api.deleteTitle(title.id);
        await loadTitles();
    } catch (error) {
        showError(error.message);
    }
}

titleForm.addEventListener('submit', async (event) => {
    // Without this, the browser would submit the form the old way and reload the page.
    event.preventDefault();
    const formData = new FormData(titleForm);

    try {
        await api.createTitle({
            name: formData.get('name'),
            type: formData.get('type'),
            // Form values are always strings, but the API expects the year as a number.
            year: Number(formData.get('year')),
            // An unticked checkbox is left out of the form data completely.
            watched: formData.has('watched'),
        });
        titleForm.reset();
        await loadTitles();
    } catch (error) {
        showError(error.message);
    }
});

loadTitles();
