class OpeningTrend {
    #chartTabId;
    #chartUrl;
    #chartCanvas;
    #emailChoices;
    #viewTypes;
    #chartData;

    static HOURS_KEY = 'hours';
    static DAYS_KEY = 'days';

    constructor(target) {
        this.#chartTabId = mQuery(target).attr('href');
        this.#chartUrl = mQuery(this.#chartTabId).attr('data-graph-url');
        this.#chartCanvas = mQuery(`${this.#chartTabId} canvas.chart`);
        this.#emailChoices = mQuery(`${this.#chartTabId} [data-dynamic="chart-select"]`);
        this.#viewTypes = mQuery(`${this.#chartTabId} [data-option]`);
        this.#chartData = mQuery(`${this.#chartTabId} div.chart-data`);
    }

    #parseResponse(response) {
        return JSON.parse(response);
    }

    #updateChart(chart, data) {
        if (chart) {
            chart.data = data;
            chart.update();
        }
    }

    #updateChartData(chart, parsedResponse) {
        const activeViewId = this.#getCurrentActiveView();
        const data = (activeViewId === OpeningTrend.HOURS_KEY) ? this.#getHoursData(parsedResponse) : this.#getDaysData(parsedResponse);

        this.#chartData.text(JSON.stringify(data));
        this.#setChartViewButtons(parsedResponse);
        this.#updateChart(chart, data);
    }

    #getDaysData(data) {
        return data[OpeningTrend.DAYS_KEY] ?? '';
    }

    #getHoursData(data) {
        return data[OpeningTrend.HOURS_KEY] ?? '';
    }

    #getEmailValues(event) {
        return mQuery(event.currentTarget).val();
    }

    #setViewTypeActive(currentOption) {
        this.#viewTypes.removeClass('active');
        this.#viewTypes.removeClass('focus');
        currentOption.addClass('active');
    }

    #getViewType(id) {
        return mQuery(`label#${id}`);
    }

    #setChartViewButtons(data) {
        const days = this.#getDaysData(data);
        const hours = this.#getHoursData(data);

        this.#getViewType(OpeningTrend.DAYS_KEY).attr('data-series', JSON.stringify(days));
        this.#getViewType(OpeningTrend.HOURS_KEY).attr('data-series', JSON.stringify(hours));
    }

    #initDataset(response) {
        const parsedResponse = this.#parseResponse(response);
        if (!parsedResponse) return;

        this.#setChartViewButtons(parsedResponse);
        this.#chartCanvas.text(JSON.stringify(this.#getDaysData(parsedResponse)));
        Mautic.renderLineChart(this.#chartCanvas);
    }

    #addEmailSelect(chart) {
        this.#emailChoices.chosen();
        this.#emailChoices.change((event) => {
            let emailIds = this.#getEmailValues(event);

            // If no values are selected, reset to all
            if (emailIds.length === 0) {
                this.#emailChoices.val(this.#emailChoices.find('option').map(function() {
                    return this.value;
                })).trigger('chosen:updated');

                emailIds = this.#getEmailValues(event);
            }

            const query = `?ids=${encodeURIComponent(JSON.stringify(emailIds))}`;
            this.#chartData.load(`${this.#chartUrl}${query}`, '', (response) => {
                const parsedResponse = this.#parseResponse(response);
                if (!parsedResponse) return;

                this.#updateChartData(chart, parsedResponse);
            });
        });
    }

    #addDatasetChange(chart) {
        this.#viewTypes.on('click', (event) => {
            const currentOption = mQuery(event.currentTarget);
            if (chart && currentOption) {
                chart.data = JSON.parse(currentOption.attr('data-series'));
                chart.update();
                this.#setViewTypeActive(currentOption);
            }
        });
    }

    #getCurrentActiveView() {
        const activeView = Array.prototype.find.call(this.#viewTypes, view => mQuery(view).hasClass('active'));
        return activeView ? activeView.id : null;
    }

    load() {
        this.#chartData.load(this.#chartUrl, '', (response) => {
            if (response) {
                this.#initDataset(response);
                const chart = Mautic.chartObjects.find(item => mQuery(item.canvas).attr('id') === 'opening-trend');
                this.#addEmailSelect(chart);
                this.#addDatasetChange(chart);
            }
        });
    }

    isRendered() {
        return !JSON.parse(this.#chartCanvas.text());
    }
}

Mautic.initOpeningTrend = (target) => {
    const openingTrend = new OpeningTrend(target);
   // if (!openingTrend.isRendered()) {
        openingTrend.load();
   // }

    return openingTrend;
}