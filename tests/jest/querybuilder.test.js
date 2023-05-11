global.fetch = require('node-fetch');

import {
    createQuery,
    queryGetMany,
    queryFirstMany,
    setQueryBuilderEndPoint,
    YEAR
} from "../../resources/js/lib/querybuilder/QueryBuilder";
import {withRelationBelongsTo, withRelationHasMany} from "../../resources/js/lib/querybuilder/relations";
import {expect, jest, test} from '@jest/globals';
setQueryBuilderEndPoint('http://localhost:8322/api/query-builder/handle');

test('TestQueryMany', async () => {

    const [transactionTypes, connectionUnits] = await queryGetMany([
        createQuery('transaction_types'), createQuery('connection_units')
    ]);

    expect(transactionTypes.length).toBeGreaterThan(0);
    expect(connectionUnits.length).toBeGreaterThan(0);
});

test('TestFirstMany', async () => {

    const [transactionType, connectionUnit, connectionUnit2] = await queryFirstMany([
        createQuery('transaction_types').limit(1),
        createQuery('connection_units').limit(1),
        createQuery('connection_units').whereEquals('id', 99999),
    ]);

    expect(transactionType !== null).toBe(true);
    expect(connectionUnit !== null).toBe(true);
    expect(connectionUnit2).toBeNull();
})


test('TestRelationHasMany', async () => {
    const transactionTypes = await createQuery('transaction_types')
        .get();

    expect(transactionTypes.length).toBeGreaterThan(0);

    await withRelationHasMany(transactionTypes, {
        table: 'transaction_type_codes',
        alias: 'TransactionTypeCodes',
        foreignKey: 'transaction_type_id',
        localKey: 'id',
    });

    expect(transactionTypes[0].TransactionTypeCodes.length).toBeGreaterThan(0);
    console.log(transactionTypes[3].TransactionTypeCodes)

})

// yarn test -t TestQueryBuilderGroupBy
test('TestQueryBuilderGroupBy', async () => {
    const entries = await createQuery('report_transaction_types')
        .select(['id', 'transaction_type_id', 'total_count', 'total_amount'])
        .whereEquals('month', 9)
        .whereEquals('year', 2022)
        .get();


    await withRelationBelongsTo(entries, {
        select: ['id', 'name'],
        table: 'transaction_types',
        alias: 'TransactionType',
        foreignKey: 'transaction_type_id'
    })

    console.log(entries);

})

// yarn test -t TestQueryBuilder
test('TestQueryBuilder', async () => {


    const q = new QueryBuilder();
    let page = 1;
    const limit = 50;
    let offset = (page - 1) * limit;

    const data = createQuery('connection_units')
        .select([YEAR('create_date').as('Y')])
        .whereEquals('unit_type', 1)
        .whereNotNull('create_date')
        .whereQuery((query) => {
            query.whereIn('status', [1, 2, 3]).orWhereEquals('month', 12);
        })
        //.groupBy('id')
        .orderBy('id', 'desc')
        .limit(1)
        .offset(offset)
        .get();

    console.log(data);


    const query = createQuery('users')
        .select(['id', 'email', 'status'])
        .whereEquals('hello', 'world')
        .orWhere('id', '>', 1)
        .whereNull('deleted_at')
        .whereQuery(query => {
            query.whereIn('status', [1, 2, 3])
                .orWhereEquals('id', 100);
        })
        .groupBy('id')
        .orderBy('id', 'asc')
        .limit(5)
        .offset(20);

})
