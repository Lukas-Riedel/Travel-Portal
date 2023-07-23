async function getExpensifyComponentForTrip(trip, isLoggedIn) {
    if (isDayTrips(trip)) {
        return "";
    }
    
    return await getExpensifyComponent([ trip ], Object.keys(configuration.expenseTypes), isLoggedIn || Cookies.get(configuration.cookies["DisplayDetailedExpensify"]), isLoggedIn, isLoggedIn, false, true);
}

async function getExpensifyComponentForAcp(trip) {
    return await getExpensifyComponent([ trip ], Object.keys(configuration.expenseTypes), true, true, true, true, false)
}

async function getExpensifyComponentForYear(trips) {
    return await getExpensifyComponent(trips, Object.keys(configuration.expenseTypes), false, false, false, false, true);
}

async function getExpensifyComponent(trips, viableExpenseTypes, detailed, showButtons, includeAdderRows, reversedRows, showSubscriptionsSelector) {
    const expenses = trips.flatMap(trip => trip.expenses.map(expense => {
            return { 
                id: expense.id, 
                type: expense.type,
                description: expense.description,
                tripId: trip.id,
                value: expense.value,
                currency: expense.currency,
                mainCurrencyValue: expense.mainCurrencyValue
            }
        })).filter(expense => viableExpenseTypes.includes(expense.type));
    
    const totalCost = sum(expenses.map(expense => expense.mainCurrencyValue));
    
    const headerRow = "<tr><th>Kategorie</th><th>Položka</th><th>Cena</th>" + (detailed ? "<th>Přepočet</th>" : "<th>Podíl</th>") + (showButtons ? "<th></th>" : "") + "</tr>";
    
    let sumRow = "";
    let contentRows = [];
    let adderRows = [];

    if (detailed) {
        contentRows = expenses.map(expense => {
            const columns = [
                "<img src=\"" + configuration.expenseTypes[expense.type].image + "\">",
                expense.description,
                expense.value + " " + expense.currency,
                expense.mainCurrencyValue.toFixed(0) + " " + configuration.mainCurrency
            ];

            const buttons = [
                { 
                    action: "changeExpense(" + expense.id + ", '" + escapeStringForHtml(expense.description) + "', '" + expense.currency + "', '" + expense.value + "', " + expense.tripId + ")",
                    image: "img/edit.png"
                },
                {
                    action: "removeExpense(" + expense.id + ", '" + escapeStringForHtml(expense.description) + "', " + expense.tripId + ")",
                    image: "img/delete.png"
                }
            ];

            let result = columns.map(column => "<td>" + column + "</td>").join("") ;
            if (showButtons) {
                result += "<td class=\"utilitiesColumn\">" 
                    + buttons.map(button => "<a onclick=\"" + button.action + "\"><img src=\"" + button.image + "\"></a>").join("")
                    + "</td>";
            }
            return "<tr>" + result + "</tr>";
        });
        
        if (includeAdderRows) { 
            const subscriptions = await getActiveSubscriptions();

            const getAdderRow = (trip, allowedTypes, defaultValue = "") => {
                if (defaultValue !== "" && expenses.map(expense => expense.description).some(expenseDescription => expenseDescription.startsWith(defaultValue))) {
                    return "";
                }

                const idSuffix = allowedTypes.join("") + defaultValue.replace(/\s/g, "");
    
                const columns = [
                    "<select id=\"expenseType" + idSuffix + "\"" + (allowedTypes.length == 1 ? " disabled" : "") + ">" + allowedTypes.map(type => "<option value=\"" + type + "\">" + configuration.expenseTypes[type].name + "</option>") + "</select>",
                    "<input size=\"" + defaultValue.length + "\" type=\"text\" id=\"expenseDescription" + idSuffix + "\" value=\"" + defaultValue + "\"" + (defaultValue !== "" ? " disabled" : "") + ">",
                    "<input type=\"text\" id=\"expenseCost" + idSuffix + "\">" + (showSubscriptionsSelector ? "&nbsp;&nbsp;<select id=\"expenseSubscription" + idSuffix + "\"><option value=\"none\"></option>" + subscriptions.map(subscription => "<option value=\"" + subscription.id + "\">" + (subscription.description + " do " + getDateString(subscription.expiration, true)) + "</option>") + "</select>" : ""),
                    "<select id=\"expenseCurrency" + idSuffix + "\">" + configuration.currencies.map(currency => "<option value=\"" + currency + "\">" + currency + "</option>") + "</select>"
                ];

                document.addEventListener("afterInitFunction", () => {
                    const setCurrency = e => {
                        const requestedCurrency = e === undefined ? configuration.mainCurrency : e.currency;

                            for (let i = 0; i < currencySelect.options.length; ++i){
                                if (currencySelect.options[i].value === requestedCurrency){
                                    currencySelect.options[i].selected = true;
                                }
                            }
                        
                    }
                    
                    const currencySelect = document.getElementById("expenseCurrency" + idSuffix);
                    const lastExpense = getLastElement(expenses);

                    setCurrency(lastExpense);

                    document.getElementById("expenseType" + idSuffix).addEventListener("change", e => {
                        const newType = e.target.value;
                        const lastExpenseOfNewType = getLastElement(expenses.filter(expense => expense.type === newType));

                        const descriptionInput = document.getElementById("expenseDescription" + idSuffix);
                        const costInput = document.getElementById("expenseCost" + idSuffix);
                        
                        descriptionInput.value = "";
                        costInput.value = "";

                        if (newType === 'INTERCITY_TRANSPORT' && lastExpenseOfNewType !== undefined) {
                            const fromToTokens = lastExpenseOfNewType.description.split(" - ");
                            if (fromToTokens.length === 2) {
                                descriptionInput.value = fromToTokens[1] + " - ";
                            }
                        }
                        else if (newType === 'PUBLIC_TRANSPORT' || newType === 'CITY_TAX' || newType === 'PARKING') {
                            navigator.geolocation.getCurrentPosition(async position => {
                                const places = await getPlacesForTrip(getFullyQualifiedTripName(trip));
                                if (places.length === 0) {
                                    return;
                                }

                                descriptionInput.value = findMin(places, item => getDistance(position.coords, item)).name;

                                if (newType === 'PUBLIC_TRANSPORT' && lastExpenseOfNewType !== undefined && lastExpenseOfNewType === descriptionInput.value) {
                                    costInput.value = lastExpenseOfNewType.value;
                                    setCurrency(lastExpenseOfNewType);
                                }
                            });
                        }
                        else if (newType === 'FUEL' && lastExpenseOfNewType !== undefined) {
                            descriptionInput.value = lastExpenseOfNewType.description;
                            setCurrency(lastExpenseOfNewType);
                        }        
                        else if (newType === 'VISA') {
                            navigator.geolocation.getCurrentPosition(async position => {
                                const places = await getPlacesForTrip(getFullyQualifiedTripName(trip));
                                if (places.length === 0) {
                                    return;
                                }

                                descriptionInput.value = findMin(places, item => getDistance(position.coords, item)).country;
                            });
                        }                        
                        else if (newType === 'AIRPORT_TRANSFER') {
                            navigator.geolocation.getCurrentPosition(async position => {
                                const flights = await getLoggedFlights();
                                if (flights.length === 0) {
                                    return;
                                }

                                const airports = flights.map(flight => flight.from).concat(flights.map(flight => flight.to));
                                descriptionInput.value = findMin(airports, item => getDistance(position.coords, item)).name;

                                if (lastExpenseOfNewType !== undefined && lastExpenseOfNewType.description === descriptionInput.value) {
                                    costInput.value = lastExpenseOfNewType.value;
                                    setCurrency(lastExpenseOfNewType);
                                }
                            });
                        }
                    });
                });

                const buttons = [
                    { 
                        action: "addExpense('" + trip.id + "', '" + idSuffix + "')",
                        image: "img/add.png"
                    }
                ];

                return "<tr>" 
                    + columns.map(column => "<td>" + column + "</td>").join("") 
                    + "<td class=\"utilitiesColumn\">" 
                    + buttons.map(button => "<a onclick=\"" + button.action + "\"><img src=\"" + button.image + "\"></a>").join("")
                    + "</td></tr>";
            };

            if (viableExpenseTypes.includes("FLIGHT")) {
                trips.forEach(trip => {
                    trip.flights.forEach(flight => {
                        adderRows.push(getAdderRow(trip, [ "FLIGHT" ], flight.from.name + " - " + flight.to.name));
                    });
                });
            }

            if (viableExpenseTypes.includes("HOTEL")) {
                trips.forEach(trip => {
                    trip.stays.forEach(stay => {
                        adderRows.push(getAdderRow(trip, [ "HOTEL" ], stay.name));
                    });
                });
            }
    
            trips.forEach(trip => {
                adderRows.push(getAdderRow(trip, Object.keys(configuration.expenseTypes).filter(type => viableExpenseTypes.includes(type) && type !== "FLIGHT" && type !== "HOTEL")));
            })
        }

        sumRow = "<tr><td></td><td></td><td></td><td><strong>" + totalCost.toFixed(0) + " " + configuration.mainCurrency + "</strong></td>" + (showButtons ? "<td></td>" : "") + "</tr>";
    }
    else {
        const expenseTypeCosts = sorted(Object.keys(configuration.expenseTypes)
            .map(expenseType => {
                return {
                    type: expenseType,
                    cost: sum(expenses.filter(expense => expense.type == expenseType).map(expense => expense.mainCurrencyValue))
                };
            })
            .filter(expenseType => expenseType.cost > 0), (a, b) => b.cost - a.cost);
    
        contentRows = expenseTypeCosts.map(expenseTypeCost => {
            const columns = [
                "<img src=\"" + configuration.expenseTypes[expenseTypeCost.type].image + "\">",
                configuration.expenseTypes[expenseTypeCost.type].name,
                expenseTypeCost.cost.toFixed(0) + " " + configuration.mainCurrency,
                (100 * expenseTypeCost.cost / totalCost).toFixed(1) + " %"
            ];

            return "<tr>" 
                + columns.map(column => "<td>" + column + "</td>").join("") 
                + "</tr>";
        })

        sumRow = "<tr><td></td><td></td><td><strong>" + totalCost.toFixed(0) + " " + configuration.mainCurrency + "</strong></td></tr>";
    }

    const allRows = contentRows.concat(adderRows);
    if (reversedRows) {
        allRows.reverse();
    }

    return "<table>" + headerRow + allRows.join("") + sumRow + "</table>";
}

function addExpense(tripId, inputSuffix) {
    const type = document.getElementById("expenseType" + inputSuffix).value;
    const description = document.getElementById("expenseDescription" + inputSuffix).value;
    const cost = document.getElementById("expenseCost" + inputSuffix).value;
    const currency = document.getElementById("expenseCurrency" + inputSuffix).value;
    const subscriptionElement = document.getElementById("expenseSubscription" + inputSuffix);
    const subscriptionId = subscriptionElement == null || subscriptionElement.value == "none" ? undefined : subscriptionElement.value;

    if (type == "" || description == "" || cost == "" || currency == "" || isNaN(cost) || Number(cost) < 0 || currency.length != 3) {
        return;
    }

    const args = { "tripId": tripId, "cost": cost, "currency" : currency, "description": description, "type": type };
    if (subscriptionId !== undefined) {
        args["subscriptionId"] = subscriptionId;
    }
    executeAndReload("AddExpense", args);
}

function removeExpense(id, description, tripId) {
    if (confirm("Jsi si jist, že chceš odstranit " + description + "?")) {
        executeAndReload("RemoveExpense", { "expenseId": id, tripId: tripId });
    }
}

async function changeExpense(id, oldName, oldCurrency, oldCost, tripId) {
    const newName = prompt("Zadej nový popis výdaje:", oldName);

    if (newName == null || newName == "") {
        return;
    }

    const newCurrency = prompt("Zadej novou měnu výdaje:", oldCurrency);
    if (newCurrency == null || newCurrency.length != 3 || (newCurrency != configuration.mainCurrency && configuration.currencies.indexOf(newCurrency) == -1)) {
        return;
    }

    const newCost = prompt("Zadej novou hodnotu výdaje:", oldCost);
    if (newCost == null || isNaN(newCost) || Number(newCost) <= 0) {
        return;
    }

    executeAndReload("ChangeExpense", { "expenseId": id, tripId: tripId, "description": newName, "currency": newCurrency, "cost": newCost });
}