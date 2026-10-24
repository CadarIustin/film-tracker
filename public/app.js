const api = {
    async getTitles() {
        const response = await fetch('/api/titles');
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
        if (!response.ok) {
            throw new Error(`Could not add the title (status ${response.status}).`);
        }

        return response.json();
    },
};

const typeLabels = { film: 'Film', series: 'Series' };

const titleForm = document.getElementById('title-form');
const titleList = document.getElementById('title-list');
const errorMessage = document.getElementById('error-message');

function renderTitles(titles) {
    titleList.replaceChildren();

    if (titles.length === 0) {
        const cell = titleList.insertRow().insertCell();
        cell.colSpan = 4;
        cell.textContent = 'No titles yet.';
        return;
    }

    for (const title of titles) {
        const row = titleList.insertRow();
        row.insertCell().textContent = title.name;
        row.insertCell().textContent = typeLabels[title.type];
        row.insertCell().textContent = title.year;
        row.insertCell().textContent = title.watched ? 'Yes' : 'No';
    }
}

function showError(message) {
    errorMessage.textContent = message;
    errorMessage.hidden = false;
}

function hideError() {
    errorMessage.hidden = true;
}

async function loadTitles() {
    hideError();
    try {
        renderTitles(await api.getTitles());
    } catch (error) {
        showError(error.message);
    }
}

titleForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(titleForm);

    try {
        await api.createTitle({
            name: formData.get('name'),
            type: formData.get('type'),
            year: Number(formData.get('year')),
            watched: formData.has('watched'),
        });
        titleForm.reset();
        await loadTitles();
    } catch (error) {
        showError(error.message);
    }
});

loadTitles();
