import {$post} from "../../utils";
export const KEY_CURRENT_DB = 'KEY_CURRENT_DB';
export const KEY_CURRENT_DB_NAME = 'CURRENT_DB_NAME';

let endpoint = '/xadmin/query-builder/query';
const BooleanType = {
    AND: 'AND',
    OR: 'OR'
};

function quote(field) {
    return '`' + field + '`';
}

class SelectState {
    constructor(field) {
        this.field = field;
    }

    toJSON() {
        return this.field;
    }

    toSQL() {
        return quote(this.field);
    }
}

class OrderByState {
    /**
     *
     * @param field {string}
     * @param direction {string}
     */
    constructor(field, direction) {
        this.field = field;
        this.direction = direction;
    }

    toJSON() {
        return {
            field: this.field,
            direction: this.direction
        }
    }

    toSQL() {
        let {direction, field} = this;
        direction = direction.toUpperCase();

        if (field.toLowerCase() === 'RAND()') {
            return "RAND()";
        }

        if (direction !== 'ASC' && direction !== 'DESC') {
            direction = "ASC";
        }

        return quote(field) + " " + direction;
    }
}

class WhereQueryStatement {
    /**
     *
     * @param query {(QueryBuilder) => void}
     * @param boolType
     */
    constructor(query, boolType) {
        boolType = boolType === BooleanType.OR ? BooleanType.OR : BooleanType.AND;
        this.query = query;
        this.booleanType = boolType;
    }
}

class WhereStatement {
    /**
     *
     * @param field {string}
     * @param operator {string}
     * @param value {any, any[]}
     * @param boolType
     */
    constructor(field, operator, value, boolType) {
        boolType = boolType === BooleanType.OR ? BooleanType.OR : BooleanType.AND;
        if (value === undefined) {
            value = null;
        }

        this.field = field;
        this.booleanType = boolType;
        this.operator = operator;
        this.value = value;
    }

    toJSON() {
        return {
            field: this.field,
            booleanType: this.booleanType,
            operator: this.operator,
            value: this.value,
        }
    }

    toSQL(withBooleanType) {
        let {field, booleanType, operator, value} = this;

        if (!withBooleanType) {
            booleanType = '';
        }

        let quotedField = quote(this.field);

        if (operator === 'IN' || operator === 'NOT IN') {
            const bindings = this.getValues().map(e => "?");

            return booleanType + quotedField + " " + operator + " (" + bindings.join(", ") + ")";
        }

        if (operator === 'IS NULL' || operator === 'IS NOT NULL') {

            return booleanType + quotedField + " " + operator;
        }

        return booleanType + quotedField + " " + operator + " ?";
    }

    getValues() {
        let {value} = this;
        if (!Array.isArray(value)) {
            if (value === null) {
                return [];
            }

            value = [value];
        }

        return value;
    }
}

export class GroupByStatement {
    constructor(field) {
        this.field = field;
    }

    toJSON() {
        return this.field;
    }

    toSQL() {
        return quote(this.field);
    }
}

/**
 *
 * @param query {QueryBuilder}
 * @param field {string}
 * @param operator {string}
 * @param value {any}
 * @param boolType
 * @returns {QueryBuilder}
 */
function addWhereStatement(query, field, operator, value, boolType) {
    query._state.where.push(new WhereStatement(field, operator, value, boolType));
    return query;
}

/**
 *
 * @param query {QueryBuilder}
 * @returns {*}
 */
function buildSelectPart(query) {

    let selectSql = "*";
    if (query._state.select.length > 0) {
        selectSql = query._state.select.map(e => {
            return e.toSQL();
        }).join(",");
    }

    const tableName = quote(query._state.tableName);
    return `SELECT ${selectSql}
            FROM ${tableName}`
}


/**
 *
 * @param query {QueryBuilder}
 * @returns {string}
 */
function buildOrderByPart(query) {
    if (query._state.orderBy.length === 0) {
        return "";
    }


    return "ORDER BY " + query._state.orderBy.map(e => {
        return e.toSQL();
    }).join(", ");
}

/**
 *
 * @param query {QueryBuilder}
 * @returns {string}
 */
function buildLimitOffsetPart(query) {
    const {limit, offset} = query._state;
    if (limit > 0) {
        if (offset === 0) {
            return `LIMIT ${limit}`;
        }

        return `LIMIT ${offset}, ${limit}`;
    }

    return "";
}

/**
 *
 * @param query {QueryBuilder}
 * @returns {*}
 */
function buildGroupByPart(query) {
    if (query._state.groupBy.length === 0) {
        return "";
    }

    return "GROUP BY " + query._state.groupBy.map(s => {
        return s.toSQL();
    }).join(", ");
}

/**
 *
 * @param query {QueryBuilder}
 * @param withWhereStatement {boolean}
 * @returns {string|*}
 */
function buildWherePart(query, withWhereStatement) {
    if (query._state.where.length === 0) {
        return "";
    }

    const list = [];

    if (withWhereStatement) {
        list.push("WHERE");
    }

    const len = query._state.where.length;
    for (let i = 0; i < len; i++) {
        const where = query._state.where[i];

        let withBoolean = (i !== 0);

        if (where instanceof WhereQueryStatement) {
            const subQuery = new QueryBuilder();
            where.query(subQuery);

            if (subQuery._state.where.length > 0) {

                if (withBoolean) {
                    list.push(where.booleanType);
                }

                list.push("(");
                list.push(buildWherePart(subQuery, false));
                list.push(")");
                query._state.bindings.push.apply(
                    query._state.bindings,
                    subQuery._state.bindings
                )
            }

        } else {
            list.push(where.toSQL(withBoolean));
            query._state.bindings.push.apply(
                query._state.bindings,
                where.getValues()
            )
        }

    }

    return list.join(" ").trim();
}


/**
 *
 * @param query {QueryBuilder}
 * @returns {string|*}
 */
function toWhereJSON(query) {
    if (query._state.where.length === 0) {
        return {};
    }

    const list = [];


    const len = query._state.where.length;
    for (let i = 0; i < len; i++) {
        const where = query._state.where[i];

        if (where instanceof WhereQueryStatement) {
            const subQuery = new QueryBuilder();
            where.query(subQuery);

            if (subQuery._state.where.length > 0) {
                list.push({
                    type: 'array',
                    query: toWhereJSON(subQuery)
                });
            }

        } else {
            list.push({
                type: 'object',
                query: where.toJSON()
            });
        }

    }

    return list;
}

export class QueryBuilder {
    _state = {
        tag: '',
        tableName: '',
        limit: 0,
        offset: 0,
        select: [],
        where: [],
        orderBy: [],
        groupBy: [],
        bindings: []
    }

    constructor() {
    }


    /**
     *
     * @param name {string}
     * @param tag {string}
     * @return {QueryBuilder}
     */
    table(name, tag = '') {
        const builder = new QueryBuilder();
        builder._state.tableName = name;

        if (!tag) {
            tag = name;
        }

        builder._state.tag = tag;
        return builder;
    }


    /**
     *
     * @param fields {Array<string|SqlFunction|GroupConcatFunction>}
     * @return {QueryBuilder}
     */
    select(fields) {
        if (!Array.isArray(fields)) {
            throw new Error('fields must be an Array');
        }

        fields.forEach(field => {
            this._state.select.push(new SelectState(field));
        })

        return this;
    }

    /**
     *
     * @param field {string}
     * @param operator {string}
     * @param value {any}
     * @returns {QueryBuilder}
     */
    where(field, operator, value) {
        if (arguments.length === 2) {
            throw new Error('arguments.length must equals 3');
        }

        return addWhereStatement(this, field, operator, value, BooleanType.AND);
    }

    /**
     *
     * @param field {string}
     * @param operator {string}
     * @param value {any}
     * @returns {QueryBuilder}
     */
    orWhere(field, operator, value) {
        if (arguments.length === 2) {
            throw new Error('arguments.length must equals 3');
        }

        return addWhereStatement(this, field, operator, value, BooleanType.OR);
    }

    /**
     *
     * @param field {string}
     * @param value {any}
     * @returns {QueryBuilder}
     */
    whereEquals(field, value) {
        return addWhereStatement(this, field, '=', value, BooleanType.AND);
    }

    /**
     *
     * @param field {string}
     * @param value {any}
     * @returns {QueryBuilder}
     */
    orWhereEquals(field, value) {
        return addWhereStatement(this, field, '=', value, BooleanType.OR);
    }

    /**
     *
     * @param field {string}
     * @param values {Array<any>}
     * @returns {QueryBuilder}
     */
    whereIn(field, values) {
        return addWhereStatement(this, field, 'IN', values, BooleanType.AND)
    }

    /**
     *
     * @param field {string}
     * @param values {Array<any>}
     * @returns {QueryBuilder}
     */
    orWhereIn(field, values) {
        return addWhereStatement(this, field, 'IN', values, BooleanType.OR)
    }

    /**
     *
     * @param field {string}
     * @param values {Array<any>}
     * @returns {QueryBuilder}
     */
    whereNotIn(field, values) {
        return addWhereStatement(this, field, 'NOT IN', values, BooleanType.AND)
    }

    /**
     *
     * @param field {string}
     * @param values {Array<any>}
     * @returns {QueryBuilder}
     */
    orWhereNotIn(field, values) {
        return addWhereStatement(this, field, 'NOT IN', values, BooleanType.OR)
    }

    /**
     *
     * @param field {string}
     * @returns {QueryBuilder}
     */
    whereNull(field) {
        return addWhereStatement(this, field, 'IS NULL', null, BooleanType.AND)
    }

    /**
     *
     * @param field {string}
     * @returns {QueryBuilder}
     */
    orWhereNull(field) {
        return addWhereStatement(this, field, 'IS NULL', null, BooleanType.OR)
    }

    /**
     *
     * @param field {string}
     * @returns {QueryBuilder}
     */
    whereNotNull(field) {
        return addWhereStatement(this, field, 'IS NOT NULL', null, BooleanType.AND)
    }

    /**
     *
     * @param field {string}
     * @returns {QueryBuilder}
     */
    orWhereNotNull(field) {
        return addWhereStatement(this, field, 'IS NOT NULL', null, BooleanType.OR)
    }

    /**
     *
     * @param query {(QueryBuilder) => void}
     * @returns {QueryBuilder}
     */
    orWhereQuery(query) {
        this._state.where.push(new WhereQueryStatement(query, BooleanType.OR))
        return this;
    }

    /**
     *
     * @param query {(QueryBuilder) => void}
     * @returns {QueryBuilder}
     */
    whereQuery(query) {
        this._state.where.push(new WhereQueryStatement(query, BooleanType.AND))
        return this;
    }

    /**
     *
     * @param field {string}
     * @return {QueryBuilder}
     */
    groupBy(field) {
        this._state.groupBy.push(new GroupByStatement(field));
        return this;
    }

    /**
     *
     * @param field {string | SqlFunction}
     * @param direction {string}
     * @return {QueryBuilder}
     */
    orderBy(field, direction) {
        this._state.orderBy.push(new OrderByState(field, direction));
        return this;
    }

    /**
     *
     * @param value {number}
     * @return {QueryBuilder}
     */
    limit(value) {
        this._state.limit = value;
        return this;
    }

    /**
     *
     * @param value {number}
     * @return {QueryBuilder}
     */
    offset(value) {
        this._state.offset = value;
        return this;
    }

    /**
     *
     * @param page {number}
     * @param limit {number}
     * @returns {QueryBuilder}
     */
    async paginate(page, limit = 25) {
        let offset = (page - 1) * limit;
        this.limit(limit).offset(offset);
        return this;
    }

    /**
     *
     * @return {Promise<Array<any>>}
     */
    async get() {

        let uri = endpoint + '/' + this._state.tag;
        const res = await $post(uri, {
            query: this.toJSON(),
            db: localStorage.getItem(KEY_CURRENT_DB),
        }, false);

        if (res.code !== 200) {
            console.error(res);
            throw new Error(res.message);
        }

        return res.data;
    }

    /**
     *
     * @return {Promise<any> | null}
     */
    async first() {
        let uri = endpoint + '/' + this._state.tag;
        const res = await $post(uri, {
            query: this.toJSON(),
            db: localStorage.getItem(KEY_CURRENT_DB),
        }, false);

        if (res.code !== 200) {
            console.error(res);
            throw new Error(res.message);
        }

        return res.data.length > 0 ? res.data[0] : null;
    }

    toJSON() {
        return {
            table: this._state.tableName,
            select: this._state.select.map(e => e.toJSON()),
            orderBy: this._state.orderBy.map(e => e.toJSON()),
            groupBy: this._state.groupBy.map(e => e.toJSON()),
            limit: this._state.limit,
            offset: this._state.offset,
            where: toWhereJSON(this)
        }
    }

    toSQL() {
        const {tableName} = this._state;
        if (tableName === '') {
            throw Error("TableName is required");
        }


        const list = [];
        list.push(buildSelectPart(this));
        list.push(buildWherePart(this, true));
        list.push(buildGroupByPart(this));
        list.push(buildOrderByPart(this));
        list.push(buildLimitOffsetPart(this));

        return list.filter(x => x !== '').join(" ").trim();
    }
}

export class SqlField {
    constructor(name) {
        this.name = name;
        this.alias = null;
    }

    toJSON() {
        return {
            class: 'SqlField',
            name: this.name,
            alias: this.alias
        }
    }

    as(alias) {
        this.alias = alias;
        return this;
    }
}

export class SqlFunction {
    /**
     *
     * @param name
     * @param args
     */
    constructor(name, ...args) {
        this.name = name;
        this.args = args;
        this.alias = null;
    }

    toJSON() {
        return {
            class: 'SqlFunction',
            name: this.name,
            args: this.args,
            alias: this.alias,
        }
    }

    as(alias) {
        this.alias = alias;
        return this;
    }
}

class GroupConcatFunction {
    /**
     *
     * @param {string} field
     */
    constructor(field) {
        this.field = field;
        this.orderByField = null;
        this.orderByDirection = null;
        this.alias = null;
        this._separator = null;
    }

    separator(value) {
        this._separator = value;
        return this;
    }

    toJSON() {
        return {
            class: 'GroupConcatFunction',
            field: this.field,
            orderByField: this.orderByField,
            orderByDirection: this.orderByDirection,
            alias: this.alias,
            separator: this._separator,
        }
    }

    /**
     *
     * @param {string} field
     * @param {string} direction
     */
    orderBy(field, direction = 'ASC') {
        this.orderByField = field;
        this.orderByDirection = direction.toUpperCase() === 'DESC' ? 'DESC' : 'ASC';
        return this;
    }

    as(alias) {
        this.alias = alias;
        return this;
    }
}

/**
 *
 * @param field {string}
 * @returns {SqlFunction}
 * @constructor
 */
export function SUM(field) {
    return new SqlFunction('SUM', field)
}

/**
 *
 * @param field {string}
 * @returns {SqlFunction}
 * @constructor
 */
export function AVG(field) {
    return new SqlFunction('AVG', field)
}

/**
 *
 * @param field {string}
 * @returns {SqlFunction}
 * @constructor
 */
export function MAX(field) {
    return new SqlFunction('MAX', field)
}

/**
 *
 * @param field {string}
 * @returns {GroupConcatFunction}
 * @constructor
 */
export function GROUP_CONCAT(field) {
    return new GroupConcatFunction(field)
}

/**
 *
 * @param field {string}
 * @returns {SqlFunction}
 * @constructor
 */
export function MIN(field) {
    return new SqlFunction('MIN', field)
}

/**
 *
 * @param field {string}
 * @returns {SqlFunction}
 * @constructor
 */
export function COUNT(field) {
    return new SqlFunction('COUNT', field)
}

/**
 *
 * @param field {string}
 * @returns {SqlFunction}
 * @constructor
 */
export function MONTH(field) {
    return new SqlFunction('MONTH', field)
}

/**
 *
 * @param field {string}
 * @returns {SqlFunction}
 * @constructor
 */
export function YEAR(field) {
    return new SqlFunction('YEAR', field)
}


/**
 *
 * @param field {string}
 * @returns {SqlFunction}
 * @constructor
 */
export function DATE(field) {
    return new SqlFunction('DATE', field)
}

/**
 *
 * @param field {string}
 * @returns {SqlField}
 * @constructor
 */
export function FIELD(field) {
    return new SqlField(field)
}

/**
 *
 * @param field {string}
 * @param format {string}
 * @returns {SqlFunction}
 * @constructor
 */
export function DATE_FORMAT(field, format) {
    return new SqlFunction('DATE_FORMAT', field, format);
}

export function setQueryBuilderEndPoint(value) {
    endpoint = value;
}


const builder = new QueryBuilder();

/**
 * Create new query
 * @param table {string}
 * @param tag {string}
 * @return {QueryBuilder}
 */
export function createQuery(table, tag = '') {
    return builder.table(table, tag);
}

/**
 * @param {Array<QueryBuilder>} queries
 */
export async function queryGetMany(queries) {
    const results = await $post(endpoint, {
        queries: queries.map(query => query.toJSON()),
        db: localStorage.getItem(KEY_CURRENT_DB),
    });

    const returnData = [];
    results.forEach(res => {
        if (res.code !== 200) {
            throw new Error(res.message);
        }

        returnData.push(res.data);
    });

    return returnData;
}

/**
 * @param {Array<QueryBuilder>} queries
 */
export async function queryFirstMany(queries) {
    const results = await queryGetMany(queries);
    return results.map(res => {

        return res.length > 0 ? res[0] : null;
    });
}
